<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Rapid\Fsm\Attributes\OnState;
use Rapid\Fsm\Attributes\OverrideApi;
use Rapid\Fsm\Attributes\WithoutAuthorizeState;
use Rapid\Fsm\Looging\PendingLog;
use Rapid\Fsm\Support\Facades\Fsm;
use Rapid\Fsm\Traits\HasEvents;

/**
 * @template T of Model
 * @extends State<T>
 */
class Context extends State
{
    use HasEvents;

    public static bool $useParameterToFindRecord = true;

    public function __construct()
    {
        static::bootIfNotBooted();
    }

    private static array $_booted = [];

    public static function bootIfNotBooted(): void
    {
        if (!isset(static::$_booted[static::class])) {
            static::boot();
            static::$_booted[static::class] = true;
        }
    }

    public static function boot(): void
    {
        foreach (trait_uses_recursive(static::class) as $trait) {
            if (method_exists(static::class, $boot = 'boot' . class_basename($trait))) {
                static::$boot();
            }
        }

        $bootStates = function (array $states) use (&$bootStates) {
            /** @var State $state */
            foreach ($states as $state) {
                $state::bootOnContext(static::class);

                if (is_a($state, Context::class, true)) {
                    $bootStates($state::states());
                }
            }
        };

        $bootStates(static::states());

        if (static::debugEnabled()) {
            (new Debugger(static::class))->run();
        }
    }

    public static function defineRoutes(
        ?Router $router = null,
        ?string $prefix = null,
        ?string $name = null,
    ): void
    {
        static::bootIfNotBooted();

        (new RouteRegistrar(
            context: static::class,
            router: $router ?? app('router'),
            prefix: $prefix,
            name: $name,
        ))->register();
    }


    public function getCurrentState(): ?State
    {
        if (!isset($this->record)) {
            return null;
        }

        $state = $this->record->current_state;

        if (is_null($state)) {
            return null;
        }

        return Fsm::getStateFor($this->record, $this, $state);
    }

    public function getCurrentDeepState(): ?State
    {
        $state = $this->getCurrentState();

        if ($state instanceof Context) {
            return $state->getCurrentDeepState() ?? $state;
        }

        return $state;
    }

    /**
     * @return State[]
     */
    public function getCurrentStateBuilding(): array
    {
        $building = [];
        $state = $this->getCurrentState();

        do $building[] = $state;
        while (($state instanceof Context) && $state = $state->getCurrentState());

        return $building;
    }

    public function useLog(): PendingLog
    {
        return new PendingLog($this);
    }

    /**
     * @template V
     * @param null|class-string<V> $state
     * @param PendingLog|null $log
     * @return State|null
     */
    public function transitionTo(?string $state, ?PendingLog $log = null): ?State
    {
        static::fire(FsmEvents::TransitionBefore, $this, $state);

        $from = $this->getCurrentState();
        $from?->onLeave();

        $this->record->update([
            'current_state' => $state,
        ]);

        Fsm::resetStateFor($this->record);

        $to = $this->getCurrentState();
        $to?->onEnter();

        static::fire(FsmEvents::Transition, $this, $from, $to);

        if (isset($log)) {
            $log->fromState = $from;
            $log->toState = $to;

            $this->logUsing($log);
        }

        return $to;
    }


    public function invokeRoute(Request $request): mixed
    {
        static::fire(FsmEvents::RoutePreparing, $this);

        $route = $request->route();
        $parameters = $route->parameters();
        $state = $parameters['_state'] ?? null;
        $edge = $parameters['_edge'];
        $withRecord = $parameters['_withRecord'];

        if ($withRecord) {
            $this->setRecord($this->findRecordUsingRequest($request));
        }

        $route->forgetParameter('_state');
        $route->forgetParameter('_edge');
        $route->forgetParameter('_withRecord');
        $route->forgetParameter('_contextId');

        $container = isset($state) ? Fsm::createStateFor($this, $state) : $this;

        if ($withRecord) {
            if (isset($state)) {
                if (method_exists($container, $edge) && $ref = new \ReflectionMethod($container, $edge)) {
                    if (!AttributeResolver::has($ref, WithoutAuthorizeState::class)) {
                        Fsm::authorize($this, $state);
                    }
                }

                if ($container instanceof Context) {
                    $container = $container->getApiTargetClass($edge);
                }
            } else {
                if (method_exists($container, $edge) && $ref = new \ReflectionMethod($container, $edge)) {
                    if ($onState = AttributeResolver::get($ref, OnState::class)) {
                        Fsm::authorize($this, $onState->states);
                    }
                }

                $container = $this->getApiTargetClass($edge);
            }
        }

        if (!method_exists($container, $edge)) {
            abort(404);
        }

        $this->onLoad();

        if ($withRecord) {
            $this->onReload();
        }

        static::fire(FsmEvents::RouteInvoking, $this, $container, $edge);

        return app(CallableDispatcher::class)->dispatch($route, $container->$edge(...));
    }

    private function getApiTargetClass(string $name): object
    {
        if (!$state = $this->getCurrentState()) {
            return $this;
        }

        if (!method_exists($state, $name)) {
            return $this;
        }

        if (!AttributeResolver::has(new \ReflectionMethod($state, $name), OverrideApi::class)) {
            return $this;
        }

        if ($state instanceof Context) {
            return $state->getApiTargetClass($name);
        }

        return $state;
    }


    /**
     * @return array<class-string<State>>
     */
    public static function states(): array
    {
        return [];
    }

    public static function withMiddleware(): array
    {
        return [];
    }

    public static function keyUsing(): string
    {
        if ($model = static::model()) {
            return (new $model)->getKeyName();
        }

        return 'id';
    }

    public static function defaultCompare(): ?int
    {
        return null;
    }

    public static function defaultDenyStatus(): ?int
    {
        return null;
    }

    public static function debugEnabled(): bool
    {
        return config('fsm.debug');
    }

    public function onReload(): void
    {
        parent::onReload();

        $this->getCurrentState()?->onReload();
    }

    public function logUsing(PendingLog $log): void
    {
    }

    public function defaultLog(): ?PendingLog
    {
        return null;
    }

    protected function findRecordUsingRequest(Request $request): Model
    {
        return static::model()::query()
            ->where(static::keyUsing(), $request->route('_contextId'))
            ->firstOrFail();
    }
}

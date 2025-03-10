<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Rapid\Fsm\Attributes\OnState;
use Rapid\Fsm\Attributes\OverrideApi;
use Rapid\Fsm\Attributes\WithoutAuthorizeState;
use Rapid\Fsm\Support\Facades\Fsm;
use Rapid\Fsm\Traits\HasEvents;

/**
 * @template T of Model
 * @extends State<T>
 */
class Context extends State
{
    use HasEvents;

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

    public static function defineRoutes(?Router $router = null): void
    {
        static::bootIfNotBooted();

        (new RouteRegistrar(static::class, $router ?? app('router')))->register();
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

    /**
     * @template V
     * @param null|class-string<V> $state
     * @return null|State|V
     */
    public function transitionTo(?string $state): ?State
    {
        static::fire(FsmEvents::TransitionBefore, $this, $state);

        $before = $this->getCurrentState();
        $before?->onLeave();

        $this->record->update([
            'current_state' => $state,
        ]);

        Fsm::resetStateFor($this->record);

        $after = $this->getCurrentState();
        $after?->onEnter();

        static::fire(FsmEvents::Transition, $this, $before, $after);

        return $after;
    }


    public function invokeRoute(): mixed
    {
        static::fire(FsmEvents::RoutePreparing, $this);

        $route = request()->route();
        $parameters = $route->parameters();
        $state = $parameters['_state'] ?? null;
        $edge = $parameters['_edge'];
        $withRecord = $parameters['_withRecord'];
        $contextId = $parameters['_contextId'] ?? null;
        $route->forgetParameter('_state');
        $route->forgetParameter('_edge');
        $route->forgetParameter('_withRecord');
        $route->forgetParameter('_contextId');

        $container = isset($state) ? Fsm::createStateFor($this, $state) : $this;

        if ($withRecord) {
            $this->setRecord(static::model()::query()->where(static::keyUsing(), $contextId)->firstOrFail());

            if (isset($state)) {
                if (method_exists($container, $edge) && $ref = new \ReflectionMethod($container, $edge)) {
                    if (!$ref->getAttributes(WithoutAuthorizeState::class)) {
                        Fsm::authorize($this, $state);
                    }
                }

                if ($container instanceof Context) {
                    $container = $container->getApiTargetClass($edge);
                }
            }
            else {
                if (method_exists($container, $edge) && $ref = new \ReflectionMethod($container, $edge)) {
                    if ($onStates = $ref->getAttributes(OnState::class)) {
                        /** @var OnState $onState */
                        $onState = $onStates[0]->newInstance();

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

        if (!(new \ReflectionMethod($state, $name))->getAttributes(OverrideApi::class)) {
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

    public static function baseUri(): string
    {
        return Str::kebab(Str::beforeLast(class_basename(static::class), 'Context'));
    }

    public static function keyUsing(): string
    {
        return 'id';
    }

    public static function defaultCompare(): int
    {
        return FsmManager::DEFAULT;
    }

    public static function defaultDenyStatus(): int
    {
        return 403;
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
}

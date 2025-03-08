<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Rapid\Fsm\Attributes\OnState;
use Rapid\Fsm\Attributes\OverrideApi;
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

        return StateMapper::getStateFor($this->record, $this, $state);
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
        $this->getCurrentState()?->onLeave();

        $this->record->update([
            'current_state' => $state,
        ]);

        StateMapper::resetStateFor($this->record);

        $this->getCurrentState()?->onEnter();

        return $this->getCurrentState();
    }


    public function invokeRoute(): mixed
    {
        $route = request()->route();
        $parameters = $route->parameters();
        $state = $parameters['state'] ?? null;
        $edge = $parameters['edge'];
        $withRecord = $parameters['withRecord'];
        $contextId = $parameters['contextId'] ?? null;
        $route->forgetParameter('state');
        $route->forgetParameter('edge');
        $route->forgetParameter('withRecord');
        $route->forgetParameter('contextId');

        $container = isset($state) ? app($state) : $this;

        if (!isset($state) && $withRecord) {
            $this->setRecord(static::model()::query()->findOrFail($contextId));

            if (method_exists($container, $edge) && $ref = new \ReflectionMethod($container, $edge)) {
                if ($onStates = $ref->getAttributes(OnState::class)) {
                    /** @var OnState $onState */
                    $onState = $onStates[0]->newInstance();

                    Fsm::authorize($this, $onState->states);
                }
            }

            $container = $this->getApiTargetClass($edge);
        }

        if (!method_exists($container, $edge)) {
            abort(404);
        }

        $this->onLoad();

        if ($withRecord) {
            $this->onReload();
        }

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

        if ((new \ReflectionMethod($state, $name))->getAttributes(OverrideApi::class)) {
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

    public function onReload(): void
    {
        parent::onReload();

        $this->getCurrentState()?->onReload();
    }
}

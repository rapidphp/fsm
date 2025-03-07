<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Rapid\Fsm\Attributes\OverrideApi;
use Rapid\Fsm\Contracts\ContextAttributeContract;
use Rapid\Fsm\Traits\HasEvents;

/**
 * @template T of Model
 * @extends State<T>
 */
class Context extends State
{
    use HasEvents;

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

        foreach ((new \ReflectionClass(static::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(ContextAttributeContract::class) as $attribute) {
                $attribute->newInstance()->boot(static::class, $method);
            }
        }

        foreach (static::states() as $state) {
            $state::bootOnContext(static::class);
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

        return StateMapper::getStateFor($this->record, $this, $state);
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
        static::bootIfNotBooted();

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

            if (
                ($currentState = $this->getCurrentState()) &&
                method_exists($currentState, $edge) &&
                (new \ReflectionMethod($currentState, $edge))->getAttributes(OverrideApi::class)
            ) {
                $container = $currentState;
            }
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

    public static function getBaseUri(): string
    {
        return 'fsm/' . Str::kebab(Str::beforeLast(class_basename(static::class), 'Context'));
    }

    public function onReload(): void
    {
        parent::onReload();

        $this->getCurrentState()?->onReload();
    }
}

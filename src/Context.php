<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Support\Str;
use Rapid\Fsm\Contracts\ContextAttributeContract;
use Rapid\Fsm\Traits\HasEvents;

/**
 * @template T of Model
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

    public static function defineRoutes(): void
    {
        static::bootIfNotBooted();

        (new RouteRegistrar(static::class))->register();
    }


    /**
     * @var T
     */
    public Model $record;

    public function setRecord(Model $record): void
    {
        $this->record = $record;
    }

    /**
     * @param array $attributes
     * @return T
     */
    public function createRecord(array $attributes): Model
    {
        $this->setRecord(
            $record = static::model()::create($attributes),
        );

        return $record;
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
     * @inheritDoc
     */
    public function transitionTo(?string $state): ?State
    {
        $this->record->update([
            'current_state' => $state,
        ]);

        StateMapper::resetStateFor($this->record);

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

        // todo

        $container = isset($state) ? new $state : $this;

        if (!isset($state) && $withRecord) {
            $this->setRecord(static::model()::query()->findOrFail($contextId));
        }

        if (!method_exists($container, $edge)) {
            abort(404);
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

    protected static string $model;

    /**
     * @return null|class-string<T>|class-string<Model>
     */
    public static function model(): ?string
    {
        return static::$model ?? null;
    }

    public static function getBaseUri(): string
    {
        return 'fsm/' . Str::kebab(Str::beforeLast(class_basename(static::class), 'Context'));
    }

}
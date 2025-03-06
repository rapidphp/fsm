<?php

namespace Rapid\Fsm;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Rapid\Fsm\Attributes\Api;
use Rapid\Fsm\Attributes\OverrideApi;
use Rapid\Fsm\Attributes\WithMiddleware;
use Rapid\Fsm\Attributes\WithoutRecord;

class RouteRegistrar
{
    public function __construct(
        /** @var class-string<Context> */
        public string $context,
    )
    {
    }

    public function register(): void
    {
        foreach ((new \ReflectionClass($this->context))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($apiAttribute = @$method->getAttributes(Api::class)[0]) {
                /** @var Api $api */
                $api = $apiAttribute->newInstance();

                $this->registerContextApi($method, $api);
            }
        }

        foreach ($this->context::states() as $state) {
            foreach ((new \ReflectionClass($state))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($apiAttribute = @$method->getAttributes(Api::class)[0]) {
                    /** @var Api $api */
                    $api = $apiAttribute->newInstance();

                    $this->registerStateApi($method, $api);
                }
            }
        }
    }

    protected function registerContextApi(\ReflectionMethod $method, Api $api): void
    {
        $uri = $this->getApiUri($method, $api, $withRecord);
        $middlewares = $this->getApiMiddlewares($method, $api);

        Route::addRoute($api->method, $uri, [$this->context, 'invokeRoute'])
            ->middleware($middlewares)
            ->name($api->name) // todo
            ->setDefaults([
                'edge' => $method->name,
                'withRecord' => $withRecord,
            ]);
    }

    protected function registerStateApi(\ReflectionMethod $method, Api $api): void
    {
        $uri = $this->getApiUri($method, $api, $withRecord);
        $middlewares = $this->getApiMiddlewares($method, $api);

        Route::addRoute($api->method, $uri, [$this->context, 'invokeRoute'])
            ->middleware($middlewares)
            ->name($api->name) // todo
            ->setDefaults([
                'state' => $method->getDeclaringClass()->name,
                'edge' => $method->name,
                'withRecord' => $withRecord,
            ]);
    }

    protected function getApiUri(\ReflectionMethod $method, Api $api, ?bool &$withRecord = null): string
    {
        $uri = $this->context::getBaseUri();

        if ($withRecord = $this->context::model() !== null && !$method->getAttributes(WithoutRecord::class)) {
            $uri .= '/{contextId}';
        }

        $uri .= '/' . trim($api->uri ?? Str::kebab($method->getName()), '/');

        return $uri;
    }

    protected function getApiMiddlewares(\ReflectionMethod $method, Api $api): array
    {
        return array_merge(
            $this->middlewareToArray($api->middleware),
            $this->getWithMiddlewareValues($method),
            $this->getWithMiddlewareValues($method->getDeclaringClass()),
            $method->getDeclaringClass()->name === $this->context ? [] : $this->getWithMiddlewareValues(new \ReflectionClass($this->context)),
            $this->context::withMiddleware(),
        );
    }

    protected function getWithMiddlewareValues($reflection): array
    {
        $result = [];

        foreach ($reflection->getAttributes(WithMiddleware::class) as $attribute) {
            /** @var WithMiddleware $withMiddleware */
            $withMiddleware = $attribute->newInstance();

            $result = array_merge($result, $this->middlewareToArray($withMiddleware->middleware));
        }

        return $result;
    }

    protected function middlewareToArray(null|string|array $middleware): array
    {
        return match (true) {
            $middleware === null   => [],
            is_string($middleware) => [$middleware],
            default                => $middleware,
        };
    }
}
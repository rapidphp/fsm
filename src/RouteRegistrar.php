<?php

namespace Rapid\Fsm;

use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Rapid\Fsm\Attributes\Api;
use Rapid\Fsm\Attributes\WithMiddleware;
use Rapid\Fsm\Attributes\WithoutRecord;
use Rapid\Fsm\Configuration\ContextConfiguration;
use Rapid\Fsm\Exceptions\ConflictDetectedException;

class RouteRegistrar
{
    public ContextConfiguration $configuration;

    public function __construct(
        /** @var class-string<Context> */
        public string  $context,
        public Router  $router,
        public ?string $prefix,
        public ?string $name,
    )
    {
        $this->configuration = $this->context::configuration();
    }

    protected array $registeredUris = [];

    public function register(): void
    {
        foreach ((new \ReflectionClass($this->context))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($api = AttributeResolver::get($method, Api::class)) {
                $this->registerContextApi($method, $api);
            }
        }

        $this->registerStateApis($this->context::states(), '');
    }

    protected function registerContextApi(\ReflectionMethod $method, Api $api): void
    {
        $uri = $this->getApiUri($method, $api, $withRecord);
        $name = $this->getApiName($method, $api);
        $middlewares = $this->getApiMiddlewares($method, $api);

        /** @var \Illuminate\Routing\Route $route */
        $route = $this->router->{$api->method}($uri, [$this->context, 'invokeRoute']);

        $route
            ->middleware($middlewares)
            ->setDefaults([
                '_edge' => $method->name,
                '_withRecord' => $withRecord,
            ]);

        if (isset($name)) {
            $route->name($name);
        }

        $this->registeredUris[] = $uri;
    }

    protected function registerStateApis(array $states, string $prefix): void
    {
        /** @var class-string<State> $state */
        foreach ($states as $state) {
            foreach ((new \ReflectionClass($state))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($api = AttributeResolver::get($method, Api::class)) {
                    $this->registerStateApi($method, $api, $prefix);
                }
            }

            if (is_a($state, Context::class, true)) {
                $this->registerStateApis(
                    $state::states(),
                    trim($prefix . '/' . $state::suffixUri(), '/'),
                );
            }
        }
    }

    protected function registerStateApi(\ReflectionMethod $method, Api $api, string $prefix): void
    {
        $uri = $this->getApiUri($method, $api, $withRecord);
        $name = $this->getApiName($method, $api);
        $middlewares = $this->getApiMiddlewares($method, $api);

        /** @var \Illuminate\Routing\Route $route */
        $route = $this->router->{$api->method}($prefix . '/' . $uri, [$this->context, 'invokeRoute']);

        $route
            ->middleware($middlewares)
            ->setDefaults([
                '_state' => $method->getDeclaringClass()->name,
                '_edge' => $method->name,
                '_withRecord' => $withRecord,
            ]);

        if (isset($name)) {
            $route->name($name);
        }

        $this->registeredUris[] = $uri;
    }

    protected function getApiUri(\ReflectionMethod $method, Api $api, ?bool &$withRecord = null): string
    {
        $uri = $this->prefix;

        if ($withRecord = $this->context::model() !== null && !AttributeResolver::has($method, WithoutRecord::class)) {
            if ($this->configuration->useContextIdInRoute()) {
                $uri .= '/{_contextId}';
            }
        }

        $uri .= '/' . trim($api->uri ?? Str::kebab($method->getName()), '/');

        if (in_array($uri, $this->registeredUris)) {
            throw new ConflictDetectedException(sprintf(
                "Api route [%s] is already registered in context [%s], at [%s::%s]",
                $uri,
                $this->context,
                $method->getDeclaringClass()->name,
                $method->name,
            ));
        }

        return $uri;
    }

    protected function getApiName(\ReflectionMethod $method, Api $api): ?string
    {
        if ($api->name === false) {
            return null;
        }

        return $this->name . ($api->name ?? Str::snake($method->getName())); // todo: what is the best case?
    }

    protected function getApiMiddlewares(\ReflectionMethod $method, Api $api): array
    {
        return array_merge(
            $this->middlewareToArray($api->middleware),
            $this->getWithMiddlewareValues($method),
            $this->getWithMiddlewareValues($method->getDeclaringClass()),
            $method->getDeclaringClass()->name === $this->context ? [] : $this->getWithMiddlewareValues(new \ReflectionClass($this->context)),
            $this->context::withMiddlewares(),
        );
    }

    protected function getWithMiddlewareValues($reflection): array
    {
        $result = [];

        foreach (AttributeResolver::all($reflection, WithMiddleware::class) as $withMiddleware) {
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
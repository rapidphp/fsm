<?php

namespace Rapid\Fsm;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use Rapid\Fsm\Attributes\IntoTransaction;
use ReflectionParameter;

class CallableDispatcher extends \Illuminate\Routing\CallableDispatcher
{
    public function __construct(protected Context $context, Container $container)
    {
        parent::__construct($container);
    }

    public function dispatch(Route $route, $callable)
    {
        $dispatch = function () use ($route, $callable) {
            return $callable(...array_values($this->resolveParameters($route, $callable)));
        };

        if ($intoTransaction = AttributeResolver::get(new \ReflectionFunction($callable), IntoTransaction::class)) {
            $dispatch = function () use ($dispatch, $intoTransaction) {
                return DB::transaction($dispatch, $intoTransaction->attempts);
            };
        }

        return $dispatch();
    }

    protected function resolveParameters(Route $route, $callable)
    {
        foreach ((new \ReflectionFunction($callable))->getParameters() as $parameter) {
            $routeParameter = $parameter->getName();

            if ($route->hasParameter($routeParameter)) {
                $this->resolveFromRouteParameter($route, $routeParameter, $parameter);
                continue;
            } elseif ($route->hasParameter($snake = Str::snake($routeParameter))) {
                $this->resolveFromRouteParameter($route, $snake, $parameter);
                continue;
            }

            $classType = Reflector::getParameterClassName($parameter);

            if ($routeParameter === 'context' && ($classType === null || is_a($classType, Context::class, true))) {
                $route->setParameter($routeParameter, $this->context);
            }
        }

        return parent::resolveParameters($route, $callable);
    }

    protected function resolveFromRouteParameter(Route $route, string $routeParameter, ReflectionParameter $parameter): void
    {
        if (!$class = Reflector::getParameterClassName($parameter)) {
            return;
        }

        if (is_a($class, Model::class, true)) {
            if ($parameter->isDefaultValueAvailable() && $route->parameter($routeParameter) === null) {
                return;
            }

            if ($callback = \Illuminate\Support\Facades\Route::getBindingCallback($routeParameter)) {
                $value = $callback($route->parameter($routeParameter));
            } else {
                $value = $class::query()
                    ->where(
                        $route->bindingFieldFor($routeParameter) ?? (new $class)->getKeyName(),
                        $route->parameter($routeParameter),
                    )
                    ->firstOrFail();
            }

            $route->setParameter($routeParameter, $value);
        }
    }
}
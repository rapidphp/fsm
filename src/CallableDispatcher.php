<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use Rapid\Fsm\Attributes\IntoTransaction;

class CallableDispatcher extends \Illuminate\Routing\CallableDispatcher
{
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

            if (!$route->hasParameter($routeParameter)) {
                if ($route->hasParameter($snake = Str::snake($routeParameter))) {
                    $routeParameter = $snake;
                } else {
                    continue;
                }
            }

            if (!$class = Reflector::getParameterClassName($parameter)) {
                continue;
            }

            if (!is_a($class, Model::class, true)) {
                continue;
            }

            if ($parameter->isDefaultValueAvailable() && $route->parameter($routeParameter) === null) {
                continue;
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

        return parent::resolveParameters($route, $callable);
    }
}
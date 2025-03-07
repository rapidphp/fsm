<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Reflector;

class CallableDispatcher extends \Illuminate\Routing\CallableDispatcher
{
    protected function resolveParameters(Route $route, $callable)
    {
        foreach ((new \ReflectionFunction($callable))->getParameters() as $parameter) {
            $routeParameter = $parameter->getName();

            if (!$route->hasParameter($routeParameter)) {
                continue;
            }

            if (!$class = Reflector::getParameterClassName($parameter)) {
                continue;
            }

            if (!is_a($class, Model::class, true)) {
                continue;
            }

            if ($callback = \Illuminate\Support\Facades\Route::getBindingCallback($routeParameter)) {
                $value = $callback($route->parameter($routeParameter));
            } else {
                $value = $class::query()
                    ->where(
                        $route->bindingFieldFor($routeParameter) ?? (new $class)->getKeyName(),
                        $route->parameter($routeParameter)
                    )
                    ->first();

                if ($value === null && !$parameter->isDefaultValueAvailable()) {
                    abort(404);
                }
            }

            $route->setParameter($routeParameter, $value);
        }

        return parent::resolveParameters($route, $callable);
    }
}
<?php

namespace Rapid\Fsm;

use Rapid\Fsm\Attributes\Api;
use Rapid\Fsm\Attributes\IntoTransaction;
use Rapid\Fsm\Attributes\OnState;
use Rapid\Fsm\Attributes\OverrideApi;
use Rapid\Fsm\Attributes\WithMiddleware;
use Rapid\Fsm\Attributes\WithoutAuthorizeState;
use Rapid\Fsm\Attributes\WithoutRecord;
use Rapid\Fsm\Exceptions\ConflictDetectedException;

class Debugger
{
    public function __construct(
        protected string $context,
    )
    {
    }

    public function run(): void
    {
        foreach ((new \ReflectionClass($this->context))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $this->detectCantUseInContext($method, [
                OverrideApi::class,
                WithoutAuthorizeState::class,
            ]);

            $this->detectCantUseWithout($method, Api::class, [
                IntoTransaction::class,
                OnState::class,
                WithMiddleware::class,
                WithoutRecord::class,
            ]);
        }

        $this->runStates($this->context::states(), [$this->context]);
    }

    protected function runStates(array $states, array $parents): void
    {
        /** @var class-string<State> $state */
        foreach ($states as $state) {
            $class = new \ReflectionClass($state);

            $this->detectCantUseInState($class, [
                WithMiddleware::class,
            ]);

            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $this->detectCantUseInState($method, [
                    WithoutRecord::class,
                ]);

                $this->detectCantUseWithout($method, Api::class, [
                    IntoTransaction::class,
                    OnState::class,
                    WithMiddleware::class,
                ]);

                $this->detectCantUseWith($method, Api::class, [
                    OverrideApi::class,
                ]);

                if (AttributeResolver::has($method, OverrideApi::class)) {
                    $this->detectCorrectOverrideApi($state, $method->name, $parents);
                }
            }

            if (is_a($state, Context::class, true)) {
                $this->runStates($state::states(), [...$parents, $state]);
            }
        }
    }


    protected function detectCantUseWith($reflection, string $with, array $attributes): void
    {
        if (!AttributeResolver::has($reflection, $with)) {
            return;
        }

        foreach ($attributes as $attribute) {
            if (AttributeResolver::all($reflection, $attribute)) {
                $this->throwCantUseWith($attribute, $with, $reflection);
            }
        }
    }

    protected function throwCantUseWith(string $conflict, string $with, $reflection): void
    {
        throw new ConflictDetectedException(sprintf(
            "Attribute [%s] can't use with [%s] on [%s]",
            $conflict,
            $with,
            $this->formatReflectionToString($reflection),
        ));
    }

    protected function detectCantUseWithout($reflection, string $without, array $attributes): void
    {
        if (AttributeResolver::has($reflection, $without)) {
            return;
        }

        foreach ($attributes as $attribute) {
            if (AttributeResolver::has($reflection, $attribute)) {
                $this->throwCantUseWithout($attribute, $without, $reflection);
            }
        }
    }

    protected function throwCantUseWithout(string $conflict, string $with, $reflection): void
    {
        throw new ConflictDetectedException(sprintf(
            "Attribute [%s] can't use without [%s] on [%s]",
            $conflict,
            $with,
            $this->formatReflectionToString($reflection),
        ));
    }

    protected function detectCantUseInContext($reflection, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            if (AttributeResolver::has($reflection, $attribute)) {
                $this->throwCantUseInContext($attribute, $reflection);
            }
        }
    }

    protected function throwCantUseInContext(string $conflict, $reflection): void
    {
        throw new ConflictDetectedException(sprintf(
            "Attribute [%s] can't use in the context, on [%s]",
            $conflict,
            $this->formatReflectionToString($reflection),
        ));
    }

    protected function detectCantUseInState($reflection, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            if (AttributeResolver::has($reflection, $attribute)) {
                $this->throwCantUseInState($attribute, $reflection);
            }
        }
    }

    protected function throwCantUseInState(string $conflict, $reflection): void
    {
        throw new ConflictDetectedException(sprintf(
            "Attribute [%s] can't use in the state, on [%s]",
            $conflict,
            $this->formatReflectionToString($reflection),
        ));
    }

    protected function detectCorrectOverrideApi(string $state, string $name, array $parents): void
    {
        foreach (array_reverse($parents) as $parent) {
            if (method_exists($parent, $name)) {
                $reflection = new \ReflectionMethod($parent, $name);

                if (AttributeResolver::has($reflection, OverrideApi::class)) {
                    continue;
                }

                if (AttributeResolver::has($reflection, Api::class)) {
                    return;
                }
            }

            $this->throwFailureOverride($state, $name, $parent);
        }

        $this->throwFailureOverride($state, $name, head($parents));
    }

    protected function throwFailureOverride(string $state, string $name, string $problemAt): void
    {
        throw new ConflictDetectedException(sprintf(
            "Fail to override api [%s] on [%s]. Class [%s] is not contains this api",
            $name,
            $state,
            $problemAt,
        ));
    }

    protected function formatReflectionToString($reflection): string
    {
        return match (true) {
            $reflection instanceof \ReflectionClass  => $reflection->name,
            $reflection instanceof \ReflectionMethod => $reflection->getDeclaringClass()->name . '::' . $reflection->name,
            is_string($reflection)                   => $reflection,
            is_array($reflection)                    => implode('::', $reflection),
        };
    }
}
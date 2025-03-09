<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class FsmManager
{
    public const DEFAULT = 0;
    public const INSTANCE_OF = 1;
    public const CONTAINS = 2;
    public const HEAD_IS = 3;
    public const HEAD_INSTANCE_OF = 4;
    public const DEEP_IS = 5;
    public const DEEP_INSTANCE_OF = 6;

    protected int $defaultCompare;

    public function authorize(Model|Context $context, string|array $state, int $compare = self::DEFAULT, ?int $status = null): void
    {
        $context = $context instanceof Model ? $context->context : $context;

        if ($this->is($context, $state, $compare)) {
            return;
        }

        throw Gate::deny()->withStatus($status ?? $context::defaultDenyStatus());
    }

    public function is(Model|Context $context, string|array $state, int $compare = self::DEFAULT): bool
    {
        $context = $context instanceof Model ? $context->context : $context;

        if ($compare === self::DEFAULT) {
            $compare = $this->defaultCompare ?? $context::defaultCompare();
        }

        switch ($compare) {
            case self::INSTANCE_OF:
                $currentStates = $context->getCurrentStateBuilding();
                foreach ((array)$state as $class) {
                    foreach ($currentStates as $current) {
                        if ($current instanceof $class) {
                            return true;
                        }
                    }
                }
                break;

            case self::CONTAINS:
                $currentStates = array_map(fn($st) => $st::class, $context->getCurrentStateBuilding());
                foreach ((array)$state as $class) {
                    if (in_array($class, $currentStates)) {
                        return true;
                    }
                }
                break;

            case self::HEAD_IS:
                $head = $context->getCurrentState();

                return isset($head) && in_array($head::class, (array)$state);

            case self::HEAD_INSTANCE_OF:
                $head = $context->getCurrentState();

                foreach ((array)$state as $class) {
                    if ($head instanceof $class) {
                        return true;
                    }
                }
                break;

            case self::DEEP_IS:
                $head = $context->getCurrentDeepState();

                return isset($head) && in_array($head::class, (array)$state);

            case self::DEEP_INSTANCE_OF:
                $head = $context->getCurrentDeepState();

                foreach ((array)$state as $class) {
                    if ($head instanceof $class) {
                        return true;
                    }
                }
                break;
        }

        return false;
    }

    public function getDefaultCompare(): int
    {
        return $this->defaultCompare ?? self::INSTANCE_OF;
    }

    public function setDefaultCompare(int $compare): void
    {
        $this->defaultCompare = $compare;
    }
}
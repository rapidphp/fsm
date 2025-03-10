<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use WeakMap;

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

    protected WeakMap $contexts;

    protected WeakMap $states;

    public function __construct()
    {
        $this->contexts = new WeakMap();
        $this->states = new WeakMap();
    }

    public function authorize(Model|Context $context, string|array $state, int $compare = self::DEFAULT, ?int $status = null): void
    {
        $context = $context instanceof Model ? $context->context : $context;

        if ($this->is($context, $state, $compare)) {
            return;
        }

        throw Gate::deny()->withStatus($status ?? $context::defaultDenyStatus() ?? config('fsm.authorize.status'));
    }

    public function is(Model|Context $context, string|array $state, int $compare = self::DEFAULT): bool
    {
        $context = $context instanceof Model ? $context->context : $context;

        if ($compare === self::DEFAULT) {
            $compare = $this->defaultCompare ?? $context::defaultCompare() ?? config('fsm.compare');
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

    public function getContextFor(Model $record, string $class): Context
    {
        if ($this->contexts->offsetExists($record)) {
            return $this->contexts->offsetGet($record);
        }

        $context = StateMapper::newContext($class);
        $context->setRecord($record);
        $context->onLoad();

        $this->contexts->offsetSet($record, $context);

        return $context;
    }

    public function getStateFor(Model $record, Context $context, string $alias): ?State
    {
        if ($this->states->offsetExists($record)) {
            return $this->states->offsetGet($record);
        }

        $state = StateMapper::newState($alias);

        if ($state === null) {
            return null;
        }

        $state->setParent($context);
        $state->loadRecord();
        $state->onLoad();

        $this->states->offsetSet($record, $state);

        return $state;
    }

    public function createStateFor(Context $context, string $alias): ?State
    {
        $state = StateMapper::newState($alias);

        if ($state === null) {
            return null;
        }

        $state->setParent($context);
        $state->loadRecord();
        $state->onLoad();

        return $state;
    }

    public function resetStateFor(Model $record, ?State $state = null): void
    {
        if (isset($state)) {
            $this->states->offsetUnset($record);
        } else {
            $this->states->offsetSet($record, $state);
        }
    }
}
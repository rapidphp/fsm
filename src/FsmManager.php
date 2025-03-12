<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Rapid\Fsm\Configuration\ContextConfiguration;
use Rapid\Fsm\Logging\Logger;
use WeakMap;

class FsmManager
{
    public const DEFAULT = 0b00000000;
    public const INSTANCE_OF = 0b00000001;
    public const IS = 0b00000010;

    public const CHECK_HAS = 0b00010000;
    public const CHECK_HEAD = 0b00100000;
    public const CHECK_DEEP = 0b00110000;
    public const CHECK_BUILDING = 0b01000000;

    protected int $defaultCompare;

    protected WeakMap $contexts;

    protected WeakMap $states;
    protected array $configurations = [];
    protected array $loggers = [];

    public function __construct()
    {
        $this->contexts = new WeakMap();
        $this->states = new WeakMap();
    }

    /**
     * @param class-string<Context> $context
     * @return ContextConfiguration
     */
    public function getContextConfiguration(string $context): ContextConfiguration
    {
        if (isset($this->configurations[$context])) {
            return $this->configurations[$context];
        }

        $configuration = $context::makeConfiguration();
        $configuration->setClass($context);

        return $this->configurations[$context] = $configuration;
    }

    /**
     * @param class-string<Context> $context
     * @return Logger
     */
    public function getContextLogger(string $context): Logger
    {
        if (isset($this->loggers[$context])) {
            return $this->loggers[$context];
        }

        $logger = $context::makeLogger();
        return $this->loggers[$context] = $logger;
    }

    public function authorize(Model|Context $context, string|array $state, int $compare = self::DEFAULT, ?int $status = null): void
    {
        $context = $context instanceof Model ? $context->context : $context;

        Gate::allowIf(
            $this->is($context, $state, $compare),
            code: $status ?? $context::configuration()->denyStatus() ?? config('fsm.authorize.status'),
        );
    }

    public function is(Model|Context $context, string|array $state, int $compare = self::DEFAULT): bool
    {
        $context = $context instanceof Model ? $context->context : $context;

        $check = 0b11110000 & $compare;
        $compare = 0b00001111 & $compare;

        if ($compare === self::DEFAULT) {
            $compare = 0b00001111 & ($context::configuration()->compare() ?? $this->defaultCompare ?? config('fsm.compare'));
        }

        if ($check === self::DEFAULT) {
            $check = self::CHECK_HAS;
        }

        switch ($check) {
            case self::CHECK_BUILDING:
                $building = $context->getCurrentStateBuilding();
                $state = (array)$state;
                if (count($building) !== count($state)) {
                    return false;
                }

                foreach ($building as $key => $bState) {
                    if (!$this->checkStateIs($bState, $state[$key], $compare)) {
                        return false;
                    }
                }

                return true;
                
            case self::CHECK_HAS:
                $building = $context->getCurrentStateBuilding();

                foreach ($building as $bState) {
                    if ($this->checkStateIs($bState, $state, $compare)) {
                        return true;
                    }
                }

                return false;

            case self::CHECK_HEAD:
                return $this->checkStateIs($context->getCurrentState(), $state, $compare);

            case self::CHECK_DEEP:
                return $this->checkStateIs($context->getCurrentDeepState(), $state, $compare);
        }

        return false;
    }

    protected function checkStateIs(State $state, string|array $type, int $compare): bool
    {
        switch ($compare) {
            case self::INSTANCE_OF:
                foreach ((array)$type as $class) {
                    if ($state instanceof $class) {
                        return true;
                    }
                }

                return false;

            case self::IS:
                return is_array($type) ?
                    in_array(get_class($state), $type, true) :
                    get_class($state) === $type;

            default:
                return false;
        }
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

    public function getRequestContext(?Request $request = null): ?Context
    {
        return ($request ?? request())->attributes->get(Context::class);
    }

    public function setRequestContext(Context $context, ?Request $request = null): void
    {
        ($request ?? request())->attributes->set(Context::class, $context);
    }

    public function getRequestState(?Request $request = null): ?State
    {
        return $this->getRequestContext($request)?->getCurrentState();
    }

    public function getRequestDeepState(?Request $request = null): ?State
    {
        return $this->getRequestContext($request)?->getCurrentDeepState();
    }
}
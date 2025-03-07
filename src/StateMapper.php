<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use WeakMap;

final class StateMapper
{
    protected static array $map = [];

    protected static WeakMap $contexts;

    protected static WeakMap $states;

    public static function reset(): void
    {
        self::$map = [];
    }

    public static function map(array|string $class, ?string $alias = null): void
    {
        if (is_string($class)) {
            $class = [$class => $alias];
        }

        self::$map = array_replace(self::$map, $class);
    }

    public static function getClass(string $alias): string
    {
        return array_search($alias, self::$map) ?: $alias;
    }

    public static function getAlias(string $class): string
    {
        return self::$map[$class] ?? $class;
    }

    public static function newState(string $alias): ?State
    {
        $class = self::getClass($alias);

        if (!class_exists($class) || !is_a($class, State::class, true)) {
            return null;
        }

        return app($class);
    }

    public static function newContext(string $class): ?Context
    {
        if (!class_exists($class) || !is_a($class, Context::class, true)) {
            return null;
        }

        return app($class);
    }

    public static function getContextFor(Model $record, string $class): Context
    {
        self::$contexts ??= new WeakMap();

        if (self::$contexts->offsetExists($record)) {
            return self::$contexts->offsetGet($record);
        }

        $context = self::newContext($class);
        $context->setRecord($record);

        self::$contexts->offsetSet($record, $context);

        return $context;
    }

    public static function getStateFor(Model $record, Context $context, string $alias): ?State
    {
        self::$states ??= new WeakMap();

        if (self::$states->offsetExists($record)) {
            return self::$states->offsetGet($record);
        }

        $state = self::newState($alias);

        if ($state === null) {
            return null;
        }

        $state->setParent($context);

        self::$states->offsetSet($record, $state);

        return $state;
    }

    public static function resetStateFor(Model $record, ?State $state = null): void
    {
        if (!isset(self::$states)) {
            return;
        }

        if (isset($state)) {
            self::$states->offsetUnset($record);
        } else {
            self::$states->offsetSet($record, $state);
        }
    }
}
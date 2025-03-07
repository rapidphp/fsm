<?php

namespace Rapid\Fsm;

final class StateMapper
{
    protected static array $map = [];

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

        return new $class();
    }

    public static function newContext(string $alias): ?Context
    {
        $class = self::getClass($alias);

        if (!class_exists($class) || !is_a($class, Context::class, true)) {
            return null;
        }

        return new $class();
    }
}
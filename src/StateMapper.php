<?php

namespace Rapid\Fsm;

final class StateMapper
{
    public static function getAlias(string $class): string
    {
        return self::$map[$class] ?? $class;
    }

    public static function newState(string $class): ?State
    {
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
}
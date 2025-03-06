<?php

namespace Rapid\Fsm\Traits;

use Closure;

trait HasEvents
{
    private static array $_events = [];

    public static function on(string $name, Closure $callback): void
    {
        @static::$_events[static::class][$name][] = $callback;
    }

    public static function fire(string $name, ...$args): void
    {
        foreach (static::$_events[static::class][$name] ?? [] as $callback) {
            $callback(...$args);
        }
    }
}
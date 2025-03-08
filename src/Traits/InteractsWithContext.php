<?php

namespace Rapid\Fsm\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;
use Rapid\Fsm\Context;
use Rapid\Fsm\State;
use Rapid\Fsm\StateMapper;
use Rapid\Laplus\Present\Present;

/**
 * @property-read Context $context
 * @property ?State $state
 * @property-read ?State $deepState
 */
trait InteractsWithContext
{
    use InteractsWithState;

    protected function contextClass(): string
    {
        throw new \Exception("Method [contextClass] is not implemented on [" . static::class . "]");
    }

    public static function bootInteractsWithContext(): void
    {
        if (method_exists(static::class, 'extendPresent')) {
            static::extendPresent(function (Present $present) {
                $present->string('current_state')->nullable();
            });
        }
    }

    public function context(): Attribute
    {
        return Attribute::get(function (): Context {
            return StateMapper::getContextFor($this, static::contextClass());
        });
    }

    public function state(): Attribute
    {
        return Attribute::make(
            get: function (): ?State {
                return $this->context->getCurrentState();
            },
            set: function (string $value) {
                $this->context->transitionTo($value);
            },
        );
    }

    public function deepState(): Attribute
    {
        return Attribute::get(function (): ?State {
            return $this->context->getCurrentDeepState();
        });
    }

    public static function scopeWhereStateIs(Builder $query, string $class): void
    {
        $query->where('current_state', StateMapper::getAlias($class));
    }

    public static function scopeWhereStateIsIn(Builder $query, array $classes): void
    {
        $query->whereIn('current_state', Arr::map($classes, fn($class) => StateMapper::getAlias($class)));
    }

    public static function scopeWhereState(Builder $query, string $class): void
    {
        /** @var Context $context */
        $context = $query->getModel()->context;

        $aliases = [];

        foreach ($context::states() as $state) {
            if (is_a($state, $class, true)) {
                $aliases[] = StateMapper::getAlias($state);
            }
        }

        $query->whereIn('current_state', $aliases);
    }

    public static function scopeWhereStateIn(Builder $query, array $classes): void
    {
        /** @var Context $context */
        $context = $query->getModel()->context;

        $aliases = [];

        foreach ($context::states() as $state) {
            foreach ($classes as $class) {
                if (is_a($state, $class, true)) {
                    $aliases[] = StateMapper::getAlias($state);
                    break;
                }
            }
        }

        $query->whereIn('current_state', $aliases);
    }
}
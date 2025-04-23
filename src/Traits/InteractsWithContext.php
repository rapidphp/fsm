<?php

namespace Rapid\Fsm\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;
use Rapid\Fsm\Context;
use Rapid\Fsm\State;
use Rapid\Fsm\Support\Facades\Fsm;
use Rapid\Laplus\Present\Present;

/**
 * @property-read Context $context
 * @property ?State $state
 * @property-read ?State $deepState
 */
trait InteractsWithContext
{
    use InteractsWithState;

    /**
     * @return class-string<Context>
     */
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
            return Fsm::getContextFor($this, static::contextClass());
        });
    }

    public function state(): Attribute
    {
        return Attribute::get(function (): ?State {
            return $this->context->getCurrentState();
        })->withoutObjectCaching();
    }

    public function deepState(): Attribute
    {
        return Attribute::get(function (): ?State {
            return $this->context->getCurrentDeepState();
        })->withoutObjectCaching();
    }

    public function scopeWhereStateIs(Builder $query, string $class, string $boolean = 'and', bool $not = false): void
    {
        $query->where('current_state', $not ? '!=' : '=', $this->contextClass()::getStateAliasName($class), boolean: $boolean);
    }

    public function scopeWhereStateIsNot(Builder $query, string $class, string $boolean = 'and'): void
    {
        $this->scopeWhereStateIs($query, $class, boolean: $boolean, not: true);
    }

    public function scopeOrWhereStateIs(Builder $query, string $class): void
    {
        $this->scopeWhereStateIs($query, $class, boolean: 'or', not: false);
    }

    public function scopeOrWhereStateIsNot(Builder $query, string $class): void
    {
        $this->scopeWhereStateIs($query, $class, boolean: 'or', not: true);
    }

    public function scopeWhereStateIsIn(Builder $query, array $classes, string $boolean = 'and', bool $not = false): void
    {
        $query->whereIn('current_state', Arr::map($classes, fn($class) => $this->contextClass()::getStateAliasName($class)), boolean: $boolean, not: $not);
    }

    public function scopeWhereStateIsNotIn(Builder $query, array $classes, string $boolean = 'and'): void
    {
        $this->scopeWhereStateIsIn($query, $classes, boolean: $boolean, not: true);
    }

    public function scopeOrWhereStateIsIn(Builder $query, array $classes): void
    {
        $this->scopeWhereStateIsIn($query, $classes, boolean: 'or', not: false);
    }

    public function scopeOrWhereStateIsNotIn(Builder $query, array $classes): void
    {
        $this->scopeWhereStateIsIn($query, $classes, boolean: 'or', not: true);
    }

    public function scopeWhereState(Builder $query, string $class, string $boolean = 'and', bool $not = false): void
    {
        /** @var Context $context */
        $context = $query->getModel()->context;

        $aliases = [];

        foreach ($context::states() as $name => $state) {
            if (is_a($state, $class, true)) {
                $aliases[] = $this->contextClass()::getStateAliasName($state);
            }
        }

        $query->whereIn('current_state', $aliases, boolean: $boolean, not: $not);
    }

    public function scopeWhereStateNot(Builder $query, string $class, string $boolean = 'and'): void
    {
        $this->scopeWhereState($query, $class, boolean: $boolean, not: true);
    }

    public function scopeOrWhereState(Builder $query, string $class): void
    {
        $this->scopeWhereState($query, $class, boolean: 'or', not: false);
    }

    public function scopeOrWhereStateNot(Builder $query, string $class): void
    {
        $this->scopeWhereState($query, $class, boolean: 'or', not: true);
    }

    public function scopeWhereStateIn(Builder $query, array $classes, string $boolean = 'and', bool $not = false): void
    {
        /** @var Context $context */
        $context = $query->getModel()->context;

        $aliases = [];

        foreach ($context::states() as $state) {
            foreach ($classes as $class) {
                if (is_a($state, $class, true)) {
                    $aliases[] = $this->contextClass()::getStateAliasName($state);
                    break;
                }
            }
        }

        $query->whereIn('current_state', $aliases, boolean: $boolean, not: $not);
    }

    public function scopeWhereStateNotIn(Builder $query, array $classes, string $boolean = 'and'): void
    {
        $this->scopeWhereStateIn($query, $classes, boolean: $boolean, not: true);
    }

    public function scopeOrWhereStateIn(Builder $query, array $classes): void
    {
        $this->scopeWhereStateIn($query, $classes, boolean: 'or', not: false);
    }

    public function scopeOrWhereStateNotIn(Builder $query, array $classes): void
    {
        $this->scopeWhereStateIn($query, $classes, boolean: 'or', not: true);
    }
}
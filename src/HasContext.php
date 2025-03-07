<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Rapid\Laplus\Present\Present;

/**
 * @property Context $context
 * @property State $state
 */
trait HasContext
{
    abstract protected function contextClass(): string;

    public static function bootHasContext(): void
    {
        if (method_exists(static::class, 'extendPresent')) {
            static::extendPresent(function (Present $present) {
                $present->string('current_state')->nullable();
                $present->morphs('parent')->nullable();
            });
        }
    }

    public function context(): Attribute
    {
        return Attribute::get(function () {
            return StateMapper::getContextFor($this, $this->current_state);
        });
    }

    public function state(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->context->getCurrentState();
            },
            set: function (string $value) {
                $this->context->transitionTo($value);
            },
        );
    }

    public function parent(): MorphTo
    {
        return $this->morphTo();
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
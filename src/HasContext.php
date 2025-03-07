<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;
use Rapid\Laplus\Present\Present;

/**
 * @property Context $context
 * @property State $state
 */
trait HasContext
{
    protected string $contextClass;

    public static function bootHasContext(): void
    {
        if (method_exists(static::class, 'extendPresent')) {
            static::extendPresent(function (Present $present) {
                $present->string('current_state')->nullable();
            });
        }
    }

    public function context(): Attribute
    {
        return Attribute::get(function () {
            /** @var Context $context */
            $context = new $this->contextClass;

            $context->setRecord($this);

            return $context;
        });
    }

    public function state(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->context->getCurrentState();
            },
            set: function ($value) {
                $this->current_state = is_string($value) ? $value : get_class($value);
            },
        );
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
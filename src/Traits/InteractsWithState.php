<?php

namespace Rapid\Fsm\Traits;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Rapid\Laplus\Present\Present;

trait InteractsWithState
{
    public static function bootInteractsWithState(): void
    {
        if (method_exists(static::class, 'extendPresent')) {
            static::extendPresent(function (Present $present) {
                $present->morphs('parent')->nullable();
            });
        }
    }

    public function parent(): MorphTo
    {
        return $this->morphTo();
    }
}
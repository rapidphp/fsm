<?php

namespace Rapid\Fsm\Traits;

use Rapid\Laplus\Present\Present;

trait InteractsWithFsmLog
{
    public static function bootInteractsWithFsmLog(): void
    {
        if (method_exists(static::class, 'extendPresent')) {
            static::extendPresent(function (Present $present) {
                $present->morphs('context');
                $present->string('from');
                $present->string('to');
                $present->string('using_api')->nullable();
                $present->string('additional')->nullable();
            });
        }
    }

    public function context()
    {
        return $this->morphTo();
    }
}
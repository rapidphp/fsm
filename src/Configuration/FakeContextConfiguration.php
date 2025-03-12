<?php

namespace Rapid\Fsm\Configuration;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FakeContextConfiguration extends DefaultContextConfiguration
{
    public static null|Model|Closure $nextRecordToFind = null;

    public function findRecord(Request $request): Model
    {
        return tap(value(static::$nextRecordToFind), function () {
            static::$nextRecordToFind = null;
        });
    }
}
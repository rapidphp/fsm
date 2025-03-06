<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;

class State
{
    public Context $context;

    protected static bool $multiple = true;
    protected static bool $keep = false;

    public function transitionTo(string $state): State
    {
    }

    public static function bootOnContext(string $context): void
    {
    }
}
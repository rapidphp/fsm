<?php

namespace Rapid\Fsm\Support\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Rapid\Fsm\Context;
use Rapid\Fsm\FsmManager;

/**
 * @method static void authorize(Model|Context $context, string|array $state, int $status = 403)
 * @method static bool is(Model|Context $context, string|array $state)
 */
class Fsm extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FsmManager::class;
    }
}
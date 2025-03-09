<?php

namespace Rapid\Fsm\Support\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Rapid\Fsm\Context;
use Rapid\Fsm\FsmManager;

/**
 * @method static void authorize(Model|Context $context, string|array $state, int $compare = FsmManager::DEFAULT, ?int $status = null)
 * @method static bool is(Model|Context $context, string|array $state, int $compare = FsmManager::DEFAULT)
 * @method static int getDefaultCompare()
 * @method static void setDefaultCompare(int $compare)
 */
class Fsm extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FsmManager::class;
    }
}
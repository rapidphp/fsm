<?php

namespace Rapid\Fsm\Support\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Rapid\Fsm\Context;
use Rapid\Fsm\FsmManager;
use Rapid\Fsm\State;

/**
 * @method static void authorize(Model|Context $context, string|array $state, int $compare = FsmManager::DEFAULT, ?int $status = null)
 * @method static bool is(Model|Context $context, string|array $state, int $compare = FsmManager::DEFAULT)
 * @method static int getDefaultCompare()
 * @method static void setDefaultCompare(int $compare)
 * @method static Context getContextFor(Model $record, string $class)
 * @method static null|State getStateFor(Model $record, Context $context, string $alias)
 * @method static null|State createStateFor(Context $context, string $alias)
 * @method static void resetStateFor(Model $record, ?State $state = null)
 */
class Fsm extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FsmManager::class;
    }
}
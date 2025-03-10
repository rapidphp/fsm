<?php

namespace Rapid\Fsm\Traits;

use Rapid\Fsm\Context;
use Rapid\Fsm\FsmEvents;
use Rapid\Fsm\State;

class HasLogging
{
    protected static function bootHasLogging()
    {
        static::listen(FsmEvents::RouteInvoking, function (Context $self, object $container, string $edge) {
            
        });
        static::listen(FsmEvents::Transition, function (Context $self, ?State $from, ?State $to) {

        });
    }
}
<?php

namespace Rapid\Fsm;

final class FsmEvents
{
    public const RoutePreparing = 'route:preparing';
    public const RouteInvoking = 'route:invoking';

    public const TransitionBefore = 'transition:before';
    public const Transition = 'transition';
}
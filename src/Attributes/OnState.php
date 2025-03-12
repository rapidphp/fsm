<?php

namespace Rapid\Fsm\Attributes;

use Attribute;
use Rapid\Fsm\FsmManager;

#[Attribute(Attribute::TARGET_METHOD)]
class OnState
{
    public function __construct(
        public string|array $states,
        public int          $compare = FsmManager::DEFAULT,
        public ?int         $status = null,
    )
    {
    }
}
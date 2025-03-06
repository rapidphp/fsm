<?php

namespace Rapid\Fsm\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class OnState
{
    public function __construct(
        public string|array $states,
    )
    {
    }
}
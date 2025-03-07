<?php

namespace Rapid\Fsm\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class IntoTransaction
{
    public function __construct(
        public int $attempts = 1,
    )
    {
    }
}
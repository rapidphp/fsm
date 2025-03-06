<?php

namespace Rapid\Fsm\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class WithMiddleware
{
    public function __construct(
        public string|array $middleware,
    )
    {
    }
}
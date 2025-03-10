<?php

namespace Rapid\Fsm\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Api
{
    public function __construct(
        public ?string           $uri = null,
        public string            $method = 'get',
        public null|false|string $name = null,
        public null|string|array $middleware = null,
    )
    {
    }
}
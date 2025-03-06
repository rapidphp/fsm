<?php

namespace Rapid\Fsm\Attributes;

use Attribute;
use Illuminate\Support\Facades\Route;
use Rapid\Fsm\Contracts\ContextAttributeContract;

#[Attribute(Attribute::TARGET_METHOD)]
class Api implements ContextAttributeContract
{
    public function __construct(
        public ?string           $uri = null,
        public string            $method = 'get',
        public ?string           $name = null,
        public null|string|array $middleware = null,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function boot(string $context, $reflection): void
    {
        $context::on('routes', function () use ($context, $reflection) {
            $uri =
                ('/' . $context::getBaseUri()) .
                ($reflection->getAttributes(WithoutRecord::class) ? '' : '/{contextId}')
                ('/' . $this->uri ?? $reflection->getName());

            Route::{$this->method}($uri, function () {

            });
        });
    }
}
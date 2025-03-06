<?php

namespace Rapid\Fsm\Contracts;

use Rapid\Fsm\Context;

interface ContextAttributeContract
{
    /**
     * @param class-string<Context> $context
     * @param $reflection
     * @return void
     */
    public function boot(string $context, $reflection): void;
}
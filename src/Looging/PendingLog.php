<?php

namespace Rapid\Fsm\Looging;

use Rapid\Fsm\Context;
use Rapid\Fsm\State;

class PendingLog
{
    public ?State $fromState = null;
    public ?State $toState = null;
    public array $attributes = [];
    public array $additional = [];

    public function __construct(
        public Context $context,
    )
    {
    }

    public static function make(Context $context): static
    {
        return new static($context);
    }

    public function with(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    public function additional(array $data)
    {
        $this->additional = array_merge($this->additional, $data);
        return $this;
    }

    public function transitionTo(?string $state): ?State
    {
        return $this->context->transitionTo($state, $this);
    }
}
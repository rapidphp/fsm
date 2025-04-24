<?php

namespace Rapid\Fsm\Logging;

use Rapid\Fsm\LinearContext;
use Rapid\Fsm\State;

/**
 * @property-read LinearContext $context
 */
class PendingLinearLog extends PendingLog
{
    public function __construct(LinearContext $context)
    {
        parent::__construct($context);
    }

    public function transitionToNext(): ?State
    {
        return $this->context->transitionToNext($this);
    }

    public function transitionToPrevious(): ?State
    {
        return $this->context->transitionToPrevious($this);
    }
}
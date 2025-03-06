<?php

namespace Rapid\Fsm;

class PendingEdge
{
    public function __construct(
        public string $from,
    )
    {
    }

    public string $to;

    public function to(string $state)
    {
        $this->to = $state;
        return $this;
    }
}
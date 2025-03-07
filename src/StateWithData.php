<?php

namespace Rapid\Fsm;

class StateWithData extends State
{
    public function createVia(): array
    {
        return [];
    }

    public function onEnter(): void
    {
        parent::onEnter();

        $this->createRecord($this->createVia());
    }
}
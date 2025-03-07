<?php

namespace Rapid\Fsm;

class StateWithRememberData extends State
{
    public function createVia(): array
    {
        return [];
    }

    public function onEnter(): void
    {
        parent::onEnter();

        if (!isset($this->record)) {
            $this->createRecord($this->createVia());
        }
    }
}
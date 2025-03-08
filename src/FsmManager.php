<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class FsmManager
{
    public function authorize(Model|Context $context, string|array $state, int $status = 403): void
    {
        if ($this->is($context, $state)) {
            return;
        }

        throw Gate::deny()->withStatus($status);
    }

    public function is(Model|Context $context, string|array $state): bool
    {
        $context = $context instanceof Model ? $context->context : $context;
        $currentStates = $context->getCurrentStateBuilding();

        foreach ((array)$state as $class) {
            foreach ($currentStates as $current) {
                if ($current instanceof $class) {
                    return true;
                }
            }
        }

        return false;
    }
}
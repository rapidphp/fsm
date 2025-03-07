<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;

class State
{
    public Context $context;

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    /**
     * @template V
     * @param class-string<V> $state
     * @return State|V
     */
    public function transitionTo(string $state): State
    {
        return $this->context->transitionTo($state);
    }

    public static function bootOnContext(string $context): void
    {
    }
}
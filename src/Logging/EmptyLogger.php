<?php

namespace Rapid\Fsm\Logging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Rapid\Fsm\Context;
use Rapid\Fsm\State;

class EmptyLogger implements Logger
{
    public function transition(PendingLog $log): void
    {
    }

    public function createdRecord(Context $context, Model $record): void
    {
    }

    public function deletedRecord(Context $context, Model $record): void
    {
    }

    public function requested(Context $context, Request $request, $response): void
    {
    }

    public function called(Context $context, State $container, string $edge): void
    {
    }
}
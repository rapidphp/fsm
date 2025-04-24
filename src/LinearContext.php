<?php

namespace Rapid\Fsm;

use Rapid\Fsm\Exceptions\FsmIsFinishedException;
use Rapid\Fsm\Logging\PendingLinearLog;
use Rapid\Fsm\Logging\PendingLog;

class LinearContext extends Context
{
    protected static array $path;
    protected static string $endState;

    protected static function path(): array
    {
        return static::$path ?? array_values(static::states());
    }

    protected static function endState(): ?string
    {
        return static::$endState ?? null;
    }

    public function useLog(): PendingLinearLog
    {
        return new PendingLinearLog($this);
    }

    public function transitionToNext(?PendingLog $log = null): ?State
    {
        $state = $this->state;
        $path = static::path();
        $endState = static::endState();

        if ($state !== null && $endState === $state::class) {
            throw new FsmIsFinishedException("Fsm is already finished");
        }

        if ($state !== null && false !== $index = array_search($state::class, $path, true)) {
            $nextState = @$path[$index + 1];
        } else {
            $nextState = $path ? $path[0] : null;
        }

        $isFinished = $nextState === null;

        if ($isFinished && $endState !== null) {
            $nextState = $endState;
        }

        $newState = $this->transitionTo($nextState, $log);

        if ($isFinished) {
            $this->onFinish();
        }

        return $newState;
    }

    public function transitionToPrevious(?PendingLog $log = null): ?State
    {
        $state = $this->state;
        $path = static::path();

        if ($state !== null && false !== $index = array_search($state::class, $path, true)) {
            $prevState = @$path[$index - 1];
        } else {
            $prevState = $path ? $path[0] : null;
        }

        if ($prevState === null) {
            return $state;
        }

        return $this->transitionTo($prevState, $log);
    }

    public function onFinish(): void
    {
        if ($this->parent instanceof LinearContext) {
            $this->parent->transitionToNext();
        }
    }
}
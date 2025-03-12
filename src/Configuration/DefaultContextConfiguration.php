<?php

namespace Rapid\Fsm\Configuration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Rapid\Fsm\Context;
use Rapid\Fsm\Logging\Logger;
use Rapid\Fsm\Logging\PendingLog;

class DefaultContextConfiguration implements ContextConfiguration
{
    /**
     * @var class-string<Context>
     */
    public string $class;

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function compare(): ?int
    {
        return null;
    }

    public function denyStatus(): ?int
    {
        return null;
    }

    public function debugEnabled(): bool
    {
        return config('fsm.debug');
    }

    public function defaultLog(): ?PendingLog
    {
        return null;
    }

    public function useContextIdInRoute(): bool
    {
        return true;
    }

    public function findRecord(Request $request): Model
    {
        return $this->class::model()::query()
            ->where($this->class::modelKey(), $request->route('_contextId'))
            ->firstOrFail();
    }

    public function makeLogger(): Logger
    {
        return app()->make(Logger::class);
    }

    public function abortNotFound(): void
    {
        abort(404);
    }

    public function abortWrongState(array $expected, int $check, int $compare, int $status): void
    {
        abort($status);
    }
}
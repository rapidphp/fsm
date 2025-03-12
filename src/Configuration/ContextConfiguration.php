<?php

namespace Rapid\Fsm\Configuration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Rapid\Fsm\Logging\Logger;
use Rapid\Fsm\Logging\PendingLog;
use Rapid\Fsm\State;

interface ContextConfiguration
{
    public function setClass(string $class): void;

    public function compare(): ?int;

    public function denyStatus(): ?int;

    public function debugEnabled(): bool;

    public function defaultLog(): ?PendingLog;

    public function useContextIdInRoute(): bool;

    public function findRecord(Request $request): Model;

    public function makeLogger(): Logger;
}
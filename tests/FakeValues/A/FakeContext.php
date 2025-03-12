<?php

namespace Rapid\Fsm\Tests\FakeValues\A;

use Rapid\Fsm\Attributes\Api;
use Rapid\Fsm\Attributes\WithoutRecord;
use Rapid\Fsm\Context;

class FakeContext extends Context
{
    protected static string $model = FakeModel::class;

    public static array $states = [
        FakeA::class,
        FakeB::class,
    ];

    #[Api, WithoutRecord]
    public function store()
    {
    }

    #[Api]
    public function update()
    {
    }
}
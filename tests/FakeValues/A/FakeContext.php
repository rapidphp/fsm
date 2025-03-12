<?php

namespace Rapid\Fsm\Tests\FakeValues\A;

use PHPUnit\Framework\Assert;
use Rapid\Fsm\Attributes\Api;
use Rapid\Fsm\Attributes\WithoutRecord;
use Rapid\Fsm\Configuration\FakeContextConfiguration;
use Rapid\Fsm\Context;

class FakeContext extends Context
{
    protected static string $model = FakeModel::class;
    protected static string $configurationClass = FakeContextConfiguration::class;

    public static array $states = [
        FakeA::class,
        FakeB::class,
        'c' => FakeC::class,
    ];

    public static ?string $called = null;

    #[Api, WithoutRecord]
    public function store()
    {
        Assert::assertNull($this->record ?? null);

        static::$called = 'store';
        return 'Stored';
    }

    #[Api]
    public function update()
    {
        Assert::assertInstanceOf(FakeModel::class, $this->record);

        static::$called = 'update';
        return response()->noContent();
    }

    #[Api]
    public function toA()
    {
        $this->transitionTo(FakeA::class);
    }

    #[Api]
    public function toB()
    {
        $this->transitionTo(FakeB::class);
    }

    #[Api]
    public function toC()
    {
        $this->transitionTo(FakeC::class);
    }

    #[Api]
    public function printState()
    {
        return $this->state::class;
    }
}
<?php

namespace Rapid\Fsm\Tests\Context;

use Rapid\Fsm\Exceptions\FsmIsFinishedException;
use Rapid\Fsm\LinearContext;
use Rapid\Fsm\Tests\FakeValues\A\FakeA;
use Rapid\Fsm\Tests\FakeValues\A\FakeB;
use Rapid\Fsm\Tests\FakeValues\A\FakeC;
use Rapid\Fsm\Tests\FakeValues\A\FakeModel;
use Rapid\Fsm\Tests\FakeValues\States\FakeEmptyState;
use Rapid\Fsm\Tests\TestCase;

class FsmLinearContextTest extends TestCase
{
    public static bool $assertCalled;

    public function test_transition_to_next_state_is_working()
    {
        $context = new class extends LinearContext {
            protected static array $states = [
                'a' => FakeA::class,
                'b' => FakeB::class,
                'c' => FakeC::class,
            ];
        };

        $context->setRecord(new FakeModel());

        $this->assertNull($context->state);
        $this->assertInstanceOf(FakeA::class, $context->transitionToNext());
        $this->assertInstanceOf(FakeB::class, $context->transitionToNext());
        $this->assertInstanceOf(FakeC::class, $context->transitionToNext());
        $this->assertNull($context->transitionToNext());
    }

    public function test_transition_to_previous_state_is_working()
    {
        $context = new class extends LinearContext {
            protected static array $states = [
                'a' => FakeA::class,
                'b' => FakeB::class,
                'c' => FakeC::class,
            ];
        };

        $context->setRecord((new FakeModel)->setRawAttributes(['current_state' => 'c']));

        $this->assertInstanceOf(FakeC::class, $context->state);
        $this->assertInstanceOf(FakeB::class, $context->transitionToPrevious());
        $this->assertInstanceOf(FakeA::class, $context->transitionToPrevious());
        $this->assertInstanceOf(FakeA::class, $context->transitionToPrevious());
    }

    public function test_transition_to_next_state_with_path_is_working()
    {
        $context = new class extends LinearContext {
            protected static array $states = [
                'a' => FakeA::class,
                'empty' => FakeEmptyState::class,
                'b' => FakeB::class,
                'c' => FakeC::class,
            ];

            protected static array $path = [
                FakeA::class,
                FakeB::class,
            ];

            protected static string $endState = FakeC::class;

            public function onFinish(): void
            {
                FsmLinearContextTest::$assertCalled = true;
            }
        };

        $context->setRecord(new FakeModel());

        static::$assertCalled = false;

        $this->assertNull($context->state);
        $this->assertInstanceOf(FakeA::class, $context->transitionToNext());
        $this->assertInstanceOf(FakeB::class, $context->transitionToNext());
        $this->assertFalse(static::$assertCalled);
        $this->assertInstanceOf(FakeC::class, $context->transitionToNext());
        $this->assertTrue(static::$assertCalled);

        $this->expectException(FsmIsFinishedException::class);
        $context->transitionToNext();
    }
}
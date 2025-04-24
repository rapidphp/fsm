<?php

namespace Rapid\Fsm\Tests\Context;

use Rapid\Fsm\Tests\FakeValues\A\FakeA;
use Rapid\Fsm\Tests\FakeValues\A\FakeB;
use Rapid\Fsm\Tests\FakeValues\A\FakeContext;
use Rapid\Fsm\Tests\FakeValues\A\FakeModel;
use Rapid\Fsm\Tests\TestCase;

class FsmContextTest extends TestCase
{
    public function test_getting_context_from_record_is_working()
    {
        $record = new FakeModel();

        $this->assertInstanceOf(FakeContext::class, $record->context);
        $this->assertSame($record, $record->context->record);
    }

    public function test_getting_context_from_record_will_cache()
    {
        $record = new FakeModel();

        $this->assertTrue($record->context === $record->context);
    }

    public function test_getting_state_from_record_is_working()
    {
        $record = new FakeModel([
            'current_state' => FakeA::class,
        ]);

        $this->assertInstanceOf(FakeA::class, $record->context->getCurrentState());
        $this->assertInstanceOf(FakeA::class, $record->context->state);
        $this->assertInstanceOf(FakeA::class, $record->state);

        $this->assertInstanceOf(FakeA::class, $record->context->getCurrentDeepState());
        $this->assertInstanceOf(FakeA::class, $record->context->deepState);
        $this->assertInstanceOf(FakeA::class, $record->deepState);
    }

    public function test_getting_state_will_be_cached()
    {
        $record = new FakeModel([
            'current_state' => FakeA::class,
        ]);

        $this->assertTrue($record->context->getCurrentState() === $record->context->getCurrentState());
        $this->assertTrue($record->context->getCurrentDeepState() === $record->context->getCurrentDeepState());
    }

    public function test_getting_state_with_null_value()
    {
        $record = new FakeModel([
            'current_state' => null,
        ]);

        $this->assertNull($record->state);
        $this->assertNull($record->deepState);
    }

    public function test_transition_will_change_the_state()
    {
        $record = new FakeModel([
            'current_state' => FakeA::class,
        ]);

        $this->assertInstanceOf(FakeA::class, $record->state);

        $newState = $record->context->transitionTo(FakeB::class);
        $this->assertInstanceOf(FakeB::class, $newState);
        $this->assertSame(FakeB::class, $record->current_state);
        $this->assertTrue($newState === $record->state);
    }

    public function test_transition_to_null()
    {
        $record = new FakeModel([
            'current_state' => FakeA::class,
        ]);

        $this->assertInstanceOf(FakeA::class, $record->state);

        $newState = $record->context->transitionTo(null);
        $this->assertNull($newState);
        $this->assertSame(null, $record->current_state);
        $this->assertNull($record->state);
    }

}
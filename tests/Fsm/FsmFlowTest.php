<?php

namespace Rapid\Fsm\Tests\Fsm;

use Rapid\Fsm\Configuration\FakeContextConfiguration;
use Rapid\Fsm\Tests\FakeValues\A\FakeA;
use Rapid\Fsm\Tests\FakeValues\A\FakeB;
use Rapid\Fsm\Tests\FakeValues\A\FakeC;
use Rapid\Fsm\Tests\FakeValues\A\FakeContext as AContext;
use Rapid\Fsm\Tests\FakeValues\A\FakeModel;
use Rapid\Fsm\Tests\TestCase;

class FsmFlowTest extends TestCase
{
    protected function defineRoutes($router)
    {
        AContext::routes($router, prefix: 'fsm/a');
    }

    public function testCallContextWithoutRecord()
    {
        AContext::$called = null;

        $this
            ->get('fsm/a/store')
            ->assertOk()
            ->assertContent('Stored');

        $this->assertSame('store', AContext::$called);
    }

    public function testCallContextWithRecord()
    {
        AContext::$called = null;
        FakeContextConfiguration::$nextRecordToFind = new FakeModel();

        $this
            ->get('fsm/a/1/update')
            ->assertNoContent();

        $this->assertSame('update', AContext::$called);
    }

    public function testTransitions()
    {
        $record = new FakeModel([
            'current_state' => null,
        ]);

        FakeContextConfiguration::$nextRecordToFind = $record;
        $this
            ->get('fsm/a/1/to-a')
            ->assertOk();

        $this->assertSame(FakeA::class, $record->current_state);

        FakeContextConfiguration::$nextRecordToFind = $record;
        $this
            ->get('fsm/a/1/print-state')
            ->assertContent(FakeA::class);

        FakeContextConfiguration::$nextRecordToFind = $record;
        $this
            ->get('fsm/a/1/to-b')
            ->assertOk();

        $this->assertSame(FakeB::class, $record->current_state);

        FakeContextConfiguration::$nextRecordToFind = $record;
        $this
            ->get('fsm/a/1/print-state')
            ->assertContent(FakeB::class);

        FakeContextConfiguration::$nextRecordToFind = $record;
        $this
            ->get('fsm/a/1/to-c')
            ->assertOk();

        $this->assertSame('c', $record->current_state);

        FakeContextConfiguration::$nextRecordToFind = $record;
        $this
            ->get('fsm/a/1/print-state')
            ->assertContent(FakeC::class);
    }
}
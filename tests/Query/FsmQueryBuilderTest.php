<?php

namespace Rapid\Fsm\Tests\Query;

use Rapid\Fsm\Tests\FakeValues\A\FakeA;
use Rapid\Fsm\Tests\FakeValues\A\FakeB;
use Rapid\Fsm\Tests\FakeValues\A\FakeC;
use Rapid\Fsm\Tests\FakeValues\A\FakeModel;
use Rapid\Fsm\Tests\TestCase;

class FsmQueryBuilderTest extends TestCase
{
    public function test_add_simple_where_state()
    {
        $this->assertSame(
            [[
                'type' => 'In',
                'column' => 'current_state',
                'values' => [FakeA::class],
                'boolean' => 'and',
            ]],
            FakeModel::query()->whereState(FakeA::class)->getQuery()->wheres,
        );

        $this->assertSame(
            [[
                'type' => 'In',
                'column' => 'current_state',
                'values' => [FakeB::class, 'c'],
                'boolean' => 'and',
            ]],
            FakeModel::query()->whereState(FakeB::class)->getQuery()->wheres,
        );

        $this->assertSame(
            [[
                'type' => 'In',
                'column' => 'current_state',
                'values' => ['c'],
                'boolean' => 'and',
            ]],
            FakeModel::query()->whereState(FakeC::class)->getQuery()->wheres,
        );
    }

    public function test_add_where_in_states()
    {
        $this->assertSame(
            [[
                'type' => 'In',
                'column' => 'current_state',
                'values' => [FakeA::class, 'c'],
                'boolean' => 'and',
            ]],
            FakeModel::query()->whereStateIn([FakeA::class, FakeC::class])->getQuery()->wheres,
        );

        $this->assertSame(
            [[
                'type' => 'In',
                'column' => 'current_state',
                'values' => [FakeA::class, FakeB::class, 'c'],
                'boolean' => 'and',
            ]],
            FakeModel::query()->whereStateIn([FakeA::class, FakeB::class])->getQuery()->wheres,
        );
    }

    public function test_add_simple_where_is_state()
    {
        $this->assertSame(
            [[
                'type' => 'Basic',
                'column' => 'current_state',
                'operator' => '=',
                'value' => FakeA::class,
                'boolean' => 'and',
            ]],
            FakeModel::query()->whereStateIs(FakeA::class)->getQuery()->wheres,
        );

        $this->assertSame(
            [[
                'type' => 'Basic',
                'column' => 'current_state',
                'operator' => '=',
                'value' => FakeB::class,
                'boolean' => 'and',
            ]],
            FakeModel::query()->whereStateIs(FakeB::class)->getQuery()->wheres,
        );

        $this->assertSame(
            [[
                'type' => 'Basic',
                'column' => 'current_state',
                'operator' => '=',
                'value' => 'c',
                'boolean' => 'and',
            ]],
            FakeModel::query()->whereStateIs(FakeC::class)->getQuery()->wheres,
        );
    }

    public function test_add_where_is_in_states()
    {
        $this->assertSame(
            [[
                'type' => 'In',
                'column' => 'current_state',
                'values' => [FakeA::class, 'c'],
                'boolean' => 'and',
            ]],
            FakeModel::query()->whereStateIsIn([FakeA::class, FakeC::class])->getQuery()->wheres,
        );

        $this->assertSame(
            [[
                'type' => 'In',
                'column' => 'current_state',
                'values' => [FakeA::class, FakeB::class],
                'boolean' => 'and',
            ]],
            FakeModel::query()->whereStateIsIn([FakeA::class, FakeB::class])->getQuery()->wheres,
        );
    }

    public function test_add_logical_conditions_for_where_state()
    {
        $query = FakeModel::query()
            ->whereState(FakeA::class)
            ->orWhereState(FakeB::class);

        $this->assertSame([
            'type' => 'In',
            'column' => 'current_state',
            'values' => [FakeA::class],
            'boolean' => 'and',
        ], $query->getQuery()->wheres[0]);

        $this->assertSame([
            'type' => 'In',
            'column' => 'current_state',
            'values' => [FakeB::class, 'c'],
            'boolean' => 'or',
        ], $query->getQuery()->wheres[1]['query']->wheres[0]['query']->wheres[0]);

        $query = FakeModel::query()
            ->whereStateNot(FakeA::class)
            ->orWhereStateNot(FakeB::class);

        $this->assertSame([
            'type' => 'NotIn',
            'column' => 'current_state',
            'values' => [FakeA::class],
            'boolean' => 'and',
        ], $query->getQuery()->wheres[0]);

        $this->assertSame([
            'type' => 'NotIn',
            'column' => 'current_state',
            'values' => [FakeB::class, 'c'],
            'boolean' => 'or',
        ], $query->getQuery()->wheres[1]['query']->wheres[0]['query']->wheres[0]);
    }

    public function test_add_logical_conditions_for_where_state_is()
    {
        $query = FakeModel::query()
            ->whereStateIs(FakeA::class)
            ->orWhereStateIs(FakeB::class);

        $this->assertSame([
            'type' => 'Basic',
            'column' => 'current_state',
            'operator' => '=',
            'value' => FakeA::class,
            'boolean' => 'and',
        ], $query->getQuery()->wheres[0]);

        $this->assertSame([
            'type' => 'Basic',
            'column' => 'current_state',
            'operator' => '=',
            'value' => FakeB::class,
            'boolean' => 'or',
        ], $query->getQuery()->wheres[1]['query']->wheres[0]['query']->wheres[0]);

        $query = FakeModel::query()
            ->whereStateIsNot(FakeA::class)
            ->orWhereStateIsNot(FakeB::class);

        $this->assertSame([
            'type' => 'Basic',
            'column' => 'current_state',
            'operator' => '!=',
            'value' => FakeA::class,
            'boolean' => 'and',
        ], $query->getQuery()->wheres[0]);

        $this->assertSame([
            'type' => 'Basic',
            'column' => 'current_state',
            'operator' => '!=',
            'value' => FakeB::class,
            'boolean' => 'or',
        ], $query->getQuery()->wheres[1]['query']->wheres[0]['query']->wheres[0]);
    }
}
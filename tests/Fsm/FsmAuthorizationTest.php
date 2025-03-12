<?php

namespace Rapid\Fsm\Tests\Fsm;

use Rapid\Fsm\Context;
use Rapid\Fsm\FsmManager;
use Rapid\Fsm\State;
use Rapid\Fsm\Support\Facades\Fsm;
use Rapid\Fsm\Tests\FakeValues\States\FakeEmptyState;
use Rapid\Fsm\Tests\FakeValues\States\FakeEmptyState2;
use Rapid\Fsm\Tests\FakeValues\States\FakeFooState;
use Rapid\Fsm\Tests\FakeValues\States\FakeStateInterface;
use Rapid\Fsm\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FsmAuthorizationTest extends TestCase
{
    public function testFsmAuthorizeCompares()
    {
        $fake = new class extends Context {
            public function getCurrentState(): ?State
            {
                return new FakeEmptyState2();
            }

            public function getCurrentDeepState(): ?State
            {
                return new FakeFooState();
            }

            public function getCurrentStateBuilding(): array
            {
                return [
                    new FakeEmptyState2(),
                    new FakeFooState(),
                ];
            }
        };

        $this->assertTrue(Fsm::is($fake, FakeStateInterface::class));
        $this->assertTrue(Fsm::is($fake, FakeEmptyState::class));
        $this->assertTrue(Fsm::is($fake, FakeEmptyState2::class));
        $this->assertFalse(Fsm::is($fake, \stdClass::class));

        $this->assertTrue(Fsm::is($fake, FakeStateInterface::class, FsmManager::INSTANCE_OF));
        $this->assertTrue(Fsm::is($fake, FakeEmptyState::class, FsmManager::INSTANCE_OF));
        $this->assertTrue(Fsm::is($fake, FakeEmptyState2::class, FsmManager::INSTANCE_OF));
        $this->assertFalse(Fsm::is($fake, \stdClass::class, FsmManager::INSTANCE_OF));

        $this->assertTrue(Fsm::is($fake, FakeFooState::class, FsmManager::IS));
        $this->assertTrue(Fsm::is($fake, [\stdClass::class, FakeFooState::class], FsmManager::IS));
        $this->assertFalse(Fsm::is($fake, FakeStateInterface::class, FsmManager::IS));
        $this->assertFalse(Fsm::is($fake, \stdClass::class, FsmManager::IS));

        $this->assertTrue(Fsm::is($fake, FakeEmptyState2::class, FsmManager::IS | FsmManager::CHECK_HEAD));
        $this->assertFalse(Fsm::is($fake, FakeStateInterface::class, FsmManager::IS | FsmManager::CHECK_HEAD));

        $this->assertTrue(Fsm::is($fake, FakeFooState::class, FsmManager::IS | FsmManager::CHECK_DEEP));
        $this->assertFalse(Fsm::is($fake, FakeStateInterface::class, FsmManager::IS | FsmManager::CHECK_DEEP));

        $this->assertTrue(Fsm::is($fake, FakeFooState::class, FsmManager::INSTANCE_OF | FsmManager::CHECK_HAS));
        $this->assertTrue(Fsm::is($fake, FakeStateInterface::class, FsmManager::INSTANCE_OF | FsmManager::CHECK_HAS));
        $this->assertTrue(Fsm::is($fake, FakeEmptyState2::class, FsmManager::INSTANCE_OF | FsmManager::CHECK_HAS));
        $this->assertFalse(Fsm::is($fake, \stdClass::class, FsmManager::INSTANCE_OF | FsmManager::CHECK_HAS));

        $this->assertTrue(Fsm::is($fake, [FakeEmptyState2::class, FakeFooState::class], FsmManager::INSTANCE_OF | FsmManager::CHECK_BUILDING));
        $this->assertTrue(Fsm::is($fake, [FakeEmptyState2::class, [\stdClass::class, FakeFooState::class]], FsmManager::INSTANCE_OF | FsmManager::CHECK_BUILDING));
        $this->assertFalse(Fsm::is($fake, [FakeEmptyState2::class, [\stdClass::class, FakeEmptyState2::class]], FsmManager::INSTANCE_OF | FsmManager::CHECK_BUILDING));
    }

    public function testThrowAuthorization()
    {
        $fake = new class extends Context {
        };

        $this->expectException(HttpException::class);
        Fsm::authorize($fake, FakeStateInterface::class);
    }
}
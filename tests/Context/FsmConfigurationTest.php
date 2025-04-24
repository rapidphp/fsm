<?php

namespace Rapid\Fsm\Tests\Context;

use Rapid\Fsm\Configuration\ContextConfiguration;
use Rapid\Fsm\Configuration\DefaultContextConfiguration;
use Rapid\Fsm\Context;
use Rapid\Fsm\Logging\EmptyLogger;
use Rapid\Fsm\Logging\Logger;
use Rapid\Fsm\Logging\PendingLog;
use Rapid\Fsm\Tests\FakeValues\A\FakeModel;
use Rapid\Fsm\Tests\TestCase;

class FsmConfigurationTest extends TestCase
{
    public static bool $assertPassed = false;

    public function test_configure_without_default_log()
    {
        $context = new class extends Context {
            public static function makeLogger(): Logger
            {
                return new class extends EmptyLogger {
                    public function transition(PendingLog $log): void
                    {
                        FsmConfigurationTest::$assertPassed = true;
                    }
                };
            }
        };

        static::$assertPassed = false;
        $context->setRecord(new FakeModel());
        $context->transitionTo(null);

        $this->assertFalse(static::$assertPassed);
    }

    public function test_configure_with_forcing_log()
    {
        $context = new class extends Context {
            protected static bool $forceLog = true;

            public static function makeLogger(): Logger
            {
                return new class extends EmptyLogger {
                    public function transition(PendingLog $log): void
                    {
                        FsmConfigurationTest::$assertPassed = true;
                    }
                };
            }
        };

        static::$assertPassed = false;
        $context->setRecord(new FakeModel());
        $context->transitionTo(null);

        $this->assertTrue(static::$assertPassed);
    }
}
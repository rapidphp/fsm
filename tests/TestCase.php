<?php

namespace Rapid\Fsm\Tests;

use Rapid\Fsm\FsmServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{

    protected function getPackageProviders($app)
    {
        return [
            ...parent::getPackageProviders($app),
            FsmServiceProvider::class,
        ];
    }

}
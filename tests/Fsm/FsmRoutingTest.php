<?php

namespace Rapid\Fsm\Tests\Fsm;

use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Rapid\Fsm\Tests\FakeValues\A\FakeContext;
use Rapid\Fsm\Tests\TestCase;

class FsmRoutingTest extends TestCase
{
    public function testAssertionIsWorking()
    {
        $router = new Router(new Dispatcher());
        $router->group(['prefix' => 'fsm'], function (Router $router) {
            $router->get('/test');
        });
        $router->get('/');

        $this->assertRouteIsRegistered($router, 'fsm/test');
        $this->assertRouteIsRegistered($router, '/');
    }

    public function testA()
    {
        $router = new Router(new Dispatcher());
        $router->name('fsm.')->prefix('fsm')->group(function (Router $router) {
            FakeContext::routes($router, 'fake', 'fake.');
        });

        $this->assertRouteIsRegistered($router, 'fsm/fake/store', 'fsm.fake.store');
        $this->assertRouteIsRegistered($router, 'fsm/fake/{_contextId}/update', 'fsm.fake.update');
    }

    public function assertRouteIsRegistered(
        Router  $router,
        string  $uri,
        ?string $name = null,
    ): void
    {
        /** @var Route $route */
        foreach ($router->getRoutes() as $route) {
            if ($route->uri() === $uri) {
                $this->assertEquals([
                    'name' => $name,
                ], [
                    'name' => isset($name) ? $route->getName() : null,
                ]);
                return;
            }
        }

        $this->assertTrue(false, "Route [$uri] is not registered");
    }
}
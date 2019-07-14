<?php
/**
 *  * Created by mtils on 30.06.19 at 11:12.
 **/

namespace Ems\Routing;


use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\RouteScope;
use Ems\TestCase;

use Ems\Contracts\Routing\Dispatcher as DispatcherContract;
use Ems\TestData;
use function iterator_to_array;

class DispatcherTest extends TestCase
{
    use TestData;
    protected static $testRoutes;

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass()
    {
        static::$testRoutes = static::includeDataFile('routing/basic-routes.php');
    }


    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(DispatcherContract::class, $this->make());
    }

    /**
     * @test
     */
    public function it_registers_routes()
    {

        $dispatcher = $this->make();

        $dispatcher->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                      ->name('addresses.index')
                      ->scope('default', 'admin')
                      ->clientType('web', 'api')
                      ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->put('addresses/{address}/edit', 'AddressController::update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });

        $routes = iterator_to_array($dispatcher);

        $this->assertCount(3, $routes);

        $result = $dispatcher->dispatch('GET', 'addresses');

        $this->assertInstanceOf(Routable::class, $result);
        $this->assertEquals(Routable::CLIENT_WEB, $result->clientType());
        $this->assertEquals('GET', $result->method());
        $this->assertEquals('default', (string)$result->routeScope());
        $this->assertInstanceOf(RouteScope::class, $result->routeScope());
        $this->assertInstanceOf(Url::class, $result->url());
        $this->assertEquals('addresses', (string)$result->url());
        $this->assertEquals([], $result->routeParameters());

        $route = $result->matchedRoute();

        $this->assertEquals('AddressController::index', $route->handler);
        $this->assertEquals('addresses.index', $route->name);
        $this->assertEquals([], $route->defaults);
        $this->assertEquals(['GET'], $route->methods);
        $this->assertEquals(['web', 'api'], $route->clientTypes);
        $this->assertEquals(['auth'], $route->middlewares);
        $this->assertEquals(['default', 'admin'], $route->scopes);
        $this->assertEquals('addresses', $route->pattern);

    }

    /**
     * @test
     */
    public function it_registers_routes_with_parameters()
    {

        $dispatcher = $this->make();

        $dispatcher->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->put('addresses/{address}/edit', 'AddressController::update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });

        $routes = iterator_to_array($dispatcher);

        $this->assertCount(3, $routes);

        $result = $dispatcher->dispatch('GET', 'addresses/112/edit');

        $this->assertInstanceOf(Routable::class, $result);
        $this->assertEquals(Routable::CLIENT_WEB, $result->clientType());
        $this->assertEquals('GET', $result->method());
        $this->assertEquals('default', (string)$result->routeScope());
        $this->assertInstanceOf(RouteScope::class, $result->routeScope());
        $this->assertInstanceOf(Url::class, $result->url());
        $this->assertEquals('addresses/112/edit', (string)$result->url());
        $this->assertEquals(['address' => 112], $result->routeParameters());

        $route = $result->matchedRoute();

        $this->assertEquals('AddressController::edit', $route->handler);
        $this->assertEquals('addresses.edit', $route->name);
        $this->assertEquals([], $route->defaults);
        $this->assertEquals(['GET'], $route->methods);
        $this->assertEquals(['web', 'api'], $route->clientTypes);
        $this->assertEquals(['auth'], $route->middlewares);
        $this->assertEquals(['default', 'admin'], $route->scopes);
        $this->assertEquals('addresses/{address}/edit', $route->pattern);


    }

    /**
     * @test
     */
    public function it_registers_routes_with_optional_parameters()
    {

        $dispatcher = $this->make();

        $dispatcher->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('delivery-addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->defaults(['type' => 'main']);
        });

        $routes = iterator_to_array($dispatcher);

        $this->assertCount(3, $routes);

        $result = $dispatcher->dispatch('PUT', 'delivery-addresses');

        $this->assertInstanceOf(Routable::class, $result);
        $this->assertEquals(Routable::CLIENT_WEB, $result->clientType());
        $this->assertEquals('PUT', $result->method());
        $this->assertEquals('default', (string)$result->routeScope());
        $this->assertInstanceOf(RouteScope::class, $result->routeScope());
        $this->assertInstanceOf(Url::class, $result->url());
        $this->assertEquals('delivery-addresses', (string)$result->url());
        $this->assertEquals(['type' => 'main'], $result->routeParameters());

        $route = $result->matchedRoute();

        $this->assertEquals('AddressController::updateDelivery', $route->handler);
        $this->assertEquals('delivery-addresses.update', $route->name);
        $this->assertEquals(['type' => 'main'], $route->defaults);
        $this->assertEquals(['PUT'], $route->methods);
        $this->assertEquals(['web', 'api'], $route->clientTypes);
        $this->assertEquals(['auth'], $route->middlewares);
        $this->assertEquals(['default', 'admin'], $route->scopes);
        $this->assertEquals('delivery-addresses[/{type}]', $route->pattern);

    }

    /**
     * @test
     */
    public function it_routes_only_for_registered_clientType()
    {

        $dispatcher = $this->make();

        $dispatcher->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('api')
                ->middleware('auth');

            $collector->put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('delivery-addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->defaults(['type' => 'main']);
        });

        $this->assertInstanceOf(Routable::class, $dispatcher->dispatch('GET', 'addresses', 'web'));
        try {
            $dispatcher->dispatch('GET', 'addresses', 'api');
            $this->fail('addreses should not match in api');
        } catch (RouteNotFoundException $e) {
            $this->assertTrue(true);
        }

        $this->assertInstanceOf(Routable::class, $dispatcher->dispatch('GET', 'addresses/1234/edit', 'api'));
        try {
            $dispatcher->dispatch('GET', 'addresses/1234/edit', 'web');
            $this->fail('addresses/1234/edit should not match in web');
        } catch (RouteNotFoundException $e) {
            $this->assertTrue(true);
        }

        $this->assertInstanceOf(Routable::class, $dispatcher->dispatch('PUT', 'delivery-addresses/main', 'web'));
        $this->assertInstanceOf(Routable::class, $dispatcher->dispatch('PUT', 'delivery-addresses/main', 'api'));

    }

    /**
     * @test
     */
    public function it_routes_only_for_registered_scope()
    {

        $dispatcher = $this->make();

        $dispatcher->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('admin')
                ->middleware('auth');

            $collector->put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('delivery-addresses.update')
                ->scope('default', 'admin')
                ->middleware('auth')
                ->defaults(['type' => 'main']);
        });

        $this->assertInstanceOf(Routable::class, $dispatcher->dispatch('GET', 'addresses', 'web', 'default'));
        try {
            $dispatcher->dispatch('GET', 'addresses', 'web', 'admin');
            $this->fail('addreses should not match in scope admin');
        } catch (RouteNotFoundException $e) {
            $this->assertTrue(true);
        }

        $this->assertInstanceOf(Routable::class, $dispatcher->dispatch('GET', 'addresses/1234/edit', 'web', 'admin'));
        try {
            $dispatcher->dispatch('GET', 'addresses/1234/edit', 'web', 'default');
            $this->fail('addresses/1234/edit should not match in scope default');
        } catch (RouteNotFoundException $e) {
            $this->assertTrue(true);
        }

        $this->assertInstanceOf(Routable::class, $dispatcher->dispatch('PUT', 'delivery-addresses/main', 'web', 'default'));
        $this->assertInstanceOf(Routable::class, $dispatcher->dispatch('PUT', 'delivery-addresses/main', 'web', 'admin'));

    }

    /**
     * @test
     */
    public function getByClientType_returns_only_matching_routes()
    {

        $dispatcher = $this->make();

        $dispatcher->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('api')
                ->middleware('auth');

            $collector->put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('delivery-addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->defaults(['type' => 'main']);
        });

    }

    /**
     * @test
     */
    public function getByPattern_returns_routes_by_pattern()
    {
        $dispatcher = $this->make(true);

        $result = $dispatcher->getByPattern('users');
        $this->assertContainsOnlyInstancesOf(Route::class, $result);
        $this->assertCount(2, $result);

        $this->assertHasNObjectWith($result, ['pattern' => 'users'], 2);
        $this->assertHasNObjectWith($result, ['methods' => ['GET']], 1);
        $this->assertHasNObjectWith($result, ['methods' => ['POST']], 1);

        $this->assertEquals(['GET'], $dispatcher->getByPattern('users', 'GET')[0]->methods);

        $result = $dispatcher->getByPattern('users/{user_id}');

        $this->assertCount(3, $result);
        $this->assertHasNObjectWith($result, ['methods' => ['GET']], 1);


        $this->assertCount(0, $dispatcher->getByPattern('users/{user_id}/move'));
    }

    /**
     * @test
     */
    public function getByName_returns_routes_by_name()
    {
        $dispatcher = $this->make(true);

        foreach (static::$testRoutes as $routeData) {
            $route = $dispatcher->getByName($routeData['name']);
            $this->assertInstanceOf(Route::class, $route);
            $this->assertEquals($routeData['name'], $route->name);
        }
    }

    /**
     * @test
     * @expectedException \Ems\Contracts\Core\Errors\NotFound
     */
    public function getByName_throws_exception_if_route_not_found()
    {
        $this->make(true)->getByName('foo');
    }

    protected function fill(DispatcherContract $dispatcher)
    {
        $dispatcher->register(function (RouteCollector $collector) {
            $this->fillCollector($collector);
        });
    }

    protected function fillCollector(RouteCollector $collector)
    {
        foreach (static::$testRoutes as $routeData) {
            $collector->on($routeData['method'], $routeData['pattern'], $routeData['handler'])
                ->name($routeData['name']);
        }
    }

    protected function make($filled=false)
    {
        $dispatcher = new Dispatcher();
        if ($filled) {
            $this->fill($dispatcher);
        }
        return $dispatcher;
    }
}
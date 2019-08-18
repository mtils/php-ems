<?php
/**
 *  * Created by mtils on 30.06.19 at 11:12.
 **/

namespace Ems\Routing;


use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Contracts\Routing\RouteScope;
use Ems\Core\Input;
use Ems\Core\Lambda;
use Ems\Core\Url;
use Ems\RoutingTrait;
use Ems\TestCase;
use ReflectionException;
use function array_values;
use function func_get_args;
use function implode;
use function is_callable;
use function iterator_to_array;

class RouterTest extends TestCase
{
    use RoutingTrait;

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(RouterContract::class, $this->make());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_registers_routes()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

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

        $routes = iterator_to_array($router);

        $this->assertCount(3, $routes);

        $routable = $this->routable('addresses');
        $this->assertFalse($routable->isRouted());
        $router->route($routable);

        $this->assertEquals(Routable::CLIENT_WEB, $routable->clientType());
        $this->assertEquals('GET', $routable->method());
        $this->assertEquals('default', (string)$routable->routeScope());
        $this->assertInstanceOf(RouteScope::class, $routable->routeScope());
        $this->assertInstanceOf(UrlContract::class, $routable->url());
        $this->assertEquals('addresses', (string)$routable->url());
        $this->assertEquals([], $routable->routeParameters());

        $route = $routable->matchedRoute();

        $this->assertEquals('AddressController::index', $route->handler);
        $this->assertEquals('addresses.index', $route->name);
        $this->assertEquals([], $route->defaults);
        $this->assertEquals(['GET'], $route->methods);
        $this->assertEquals(['web', 'api'], $route->clientTypes);
        $this->assertEquals(['auth'], $route->middlewares);
        $this->assertEquals(['default', 'admin'], $route->scopes);
        $this->assertEquals('addresses', $route->pattern);
        $this->assertTrue($routable->isRouted());
        $this->assertTrue(is_callable($routable->getHandler()));

    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_registers_routes_with_parameters()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

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

        $routes = iterator_to_array($router);

        $this->assertCount(3, $routes);

        $routable = $this->routable('addresses/112/edit');
        $router->route($routable);

        $this->assertEquals(Routable::CLIENT_WEB, $routable->clientType());
        $this->assertEquals('GET', $routable->method());
        $this->assertEquals('default', (string)$routable->routeScope());
        $this->assertInstanceOf(RouteScope::class, $routable->routeScope());
        $this->assertInstanceOf(UrlContract::class, $routable->url());
        $this->assertEquals('addresses/112/edit', (string)$routable->url());
        $this->assertEquals(['address' => 112], $routable->routeParameters());

        $route = $routable->matchedRoute();

        $this->assertEquals('AddressController::edit', $route->handler);
        $this->assertEquals('addresses.edit', $route->name);
        $this->assertEquals([], $route->defaults);
        $this->assertEquals(['GET'], $route->methods);
        $this->assertEquals(['web', 'api'], $route->clientTypes);
        $this->assertEquals(['auth'], $route->middlewares);
        $this->assertEquals(['default', 'admin'], $route->scopes);
        $this->assertEquals('addresses/{address}/edit', $route->pattern);
        $this->assertTrue($routable->isRouted());
        $this->assertTrue(is_callable($routable->getHandler()));

    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_registers_routes_with_optional_parameters()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

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

        $routes = iterator_to_array($router);

        $this->assertCount(3, $routes);

        $routable = $this->routable('delivery-addresses', 'PUT');
        $router->route($routable);

        $this->assertEquals(Routable::CLIENT_WEB, $routable->clientType());
        $this->assertEquals('PUT', $routable->method());
        $this->assertEquals('default', (string)$routable->routeScope());
        $this->assertInstanceOf(RouteScope::class, $routable->routeScope());
        $this->assertInstanceOf(UrlContract::class, $routable->url());
        $this->assertEquals('delivery-addresses', (string)$routable->url());
        $this->assertEquals(['type' => 'main'], $routable->routeParameters());

        $route = $routable->matchedRoute();

        $this->assertEquals('AddressController::updateDelivery', $route->handler);
        $this->assertEquals('delivery-addresses.update', $route->name);
        $this->assertEquals(['type' => 'main'], $route->defaults);
        $this->assertEquals(['PUT'], $route->methods);
        $this->assertEquals(['web', 'api'], $route->clientTypes);
        $this->assertEquals(['auth'], $route->middlewares);
        $this->assertEquals(['default', 'admin'], $route->scopes);
        $this->assertEquals('delivery-addresses[/{type}]', $route->pattern);
        $this->assertTrue($routable->isRouted());
        $this->assertTrue(is_callable($routable->getHandler()));

    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_routes_only_for_registered_clientType()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

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


        $routable = $this->routable('addresses');
        $router->route($routable);
        $this->assertTrue($routable->isRouted());

        try {
            $routable = $this->routable('addresses', 'GET', 'api');
            $router->route($routable);
            $this->fail('addresses should not match in api');
        } catch (RouteNotFoundException $e) {
            $this->assertTrue(true);
        }

        $routable = $this->routable('addresses/1234/edit', 'GET', 'api');
        $router->route($routable);
        $this->assertTrue($routable->isRouted());

        try {
            $router->route($this->routable('addresses/1234/edit', 'GET', 'web'));
            $this->fail('addresses/1234/edit should not match in web');
        } catch (RouteNotFoundException $e) {
            $this->assertTrue(true);
        }

        $routable = $this->routable('delivery-addresses/main', 'PUT');
        $router->route($routable);
        $this->assertTrue($routable->isRouted());

        $routable = $this->routable('delivery-addresses/main', 'PUT', 'api');
        $router->route($routable);
        $this->assertTrue($routable->isRouted());


    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_routes_only_for_registered_scope()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

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

        $routable = $this->routable('addresses', 'GET', 'web', 'default');
        $router->route($routable);
        $this->assertTrue($routable->isRouted());

        try {
            $routable = $this->routable('addresses', 'GET', 'web', 'admin');
            $router->route($routable);
            $this->fail('addresses should not match in scope admin');
        } catch (RouteNotFoundException $e) {
            $this->assertTrue(true);
        }

        $routable = $this->routable('addresses/1234/edit', 'GET', 'web', 'admin');
        $router->route($routable);
        $this->assertTrue($routable->isRouted());

        try {
            $routable = $this->routable('addresses/1234/edit', 'GET', 'web', 'default');
            $router->route($routable);
            $this->fail('addresses/1234/edit should not match in scope default');
        } catch (RouteNotFoundException $e) {
            $this->assertTrue(true);
        }

        $routable = $this->routable('delivery-addresses/main', 'PUT');
        $router->route($routable);
        $this->assertTrue($routable->isRouted());

        $routable = $this->routable('delivery-addresses/main', 'PUT', 'web', 'admin');
        $router->route($routable);
        $this->assertTrue($routable->isRouted());

    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_handles_routes()
    {

        $router = $this->make();
        $router->createObjectsBy(function ($class) {
            return new $class;
        });

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', RouterTest_TestController::class.'->index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', RouterTest_TestController::class.'->edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->put('addresses/{address}/edit', RouterTest_TestController::class.'->update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });

        $routes = iterator_to_array($router);

        $this->assertCount(3, $routes);

        $routable = $this->routable('addresses/112/edit');
        $router->route($routable);

        $this->assertEquals(Routable::CLIENT_WEB, $routable->clientType());
        $this->assertEquals('GET', $routable->method());
        $this->assertEquals('default', (string)$routable->routeScope());
        $this->assertInstanceOf(RouteScope::class, $routable->routeScope());
        $this->assertInstanceOf(UrlContract::class, $routable->url());
        $this->assertEquals('addresses/112/edit', (string)$routable->url());
        $this->assertEquals(['address' => 112], $routable->routeParameters());

        $route = $routable->matchedRoute();

        $this->assertEquals(RouterTest_TestController::class . '->edit', $route->handler);
        $this->assertEquals('addresses.edit', $route->name);
        $this->assertEquals([], $route->defaults);
        $this->assertEquals(['GET'], $route->methods);
        $this->assertEquals(['web', 'api'], $route->clientTypes);
        $this->assertEquals(['auth'], $route->middlewares);
        $this->assertEquals(['default', 'admin'], $route->scopes);
        $this->assertEquals('addresses/{address}/edit', $route->pattern);
        $this->assertTrue($routable->isRouted());
        $handler = $routable->getHandler();
        $this->assertInstanceOf(Lambda::class, $handler);
        $this->assertEquals('edit was called: 112' , $handler(...array_values($routable->routeParameters())));

    }

    /**
     * @test
     */
    public function getByClientType_returns_only_matching_routes()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

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
    public function register_with_common_client_type()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->middleware('auth');

            $collector->put('addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->middleware('auth')
                ->defaults(['type' => 'main']);
        }, [RouterContract::CLIENT => 'console']);

        foreach (['index', 'edit', 'update'] as $action) {
            $this->assertEquals(['console'], $router->getByName("addresses.$action")->clientTypes);
        }
    }

    /**
     * @test
     */
    public function register_with_common_client_type_and_overwrites()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('api')
                ->middleware('auth');

            $collector->put('addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->middleware('auth')
                ->defaults(['type' => 'main']);
        }, [RouterContract::CLIENT => ['console', 'ajax']]);

        foreach (['index', 'update'] as $action) {
            $this->assertEquals(['console', 'ajax'], $router->getByName("addresses.$action")->clientTypes);
        }
        $this->assertEquals(['api'], $router->getByName("addresses.edit")->clientTypes);
    }

    /**
     * @test
     */
    public function register_with_common_scope()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->clientType('web')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->clientType('api')
                ->middleware('auth');

            $collector->put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('addresses.update')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->defaults(['type' => 'main']);
        }, [Router::SCOPE => ['default', 'admin']]);

        foreach (['index', 'edit', 'update'] as $action) {
            $this->assertEquals(['default', 'admin'], $router->getByName("addresses.$action")->scopes);
        }

    }

    /**
     * @test
     */
    public function register_with_common_scope_and_overwrites()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->clientType('web')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->clientType('api')
                ->middleware('auth');

            $collector->put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('addresses.update')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->scope('master')
                ->defaults(['type' => 'main']);
        }, [Router::SCOPE => ['default', 'admin']]);

        foreach (['index', 'edit'] as $action) {
            $this->assertEquals(['default', 'admin'], $router->getByName("addresses.$action")->scopes);
        }
        $this->assertEquals(['master'], $router->getByName("addresses.update")->scopes);

    }

    /**
     * @test
     */
    public function register_with_common_middleware()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->clientType('web');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->clientType('api');

            $collector->put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('addresses.update')
                ->clientType('web', 'api')
                ->defaults(['type' => 'main']);
        }, [Router::MIDDLEWARE => 'auth']);

        foreach (['index', 'edit', 'update'] as $action) {
            $this->assertEquals(['auth'], $router->getByName("addresses.$action")->middlewares);
        }

    }

    /**
     * @test
     */
    public function register_with_common_middleware_and_additional()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->clientType('web');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->clientType('api')
                ->middleware('has-role:moderator');

            $collector->put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('addresses.update')
                ->clientType('web', 'api')
                ->defaults(['type' => 'main']);
        }, [Router::MIDDLEWARE => 'auth']);

        foreach (['index', 'update'] as $action) {
            $this->assertEquals(['auth'], $router->getByName("addresses.$action")->middlewares);
        }

        $this->assertEquals(['auth', 'has-role:moderator'], $router->getByName("addresses.edit")->middlewares);

    }

    /**
     * @test
     */
    public function register_with_common_middleware_and_same_middleware_with_different_parameters()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->clientType('web');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->clientType('api')
                ->middleware('auth:moderator');

            $collector->put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('addresses.update')
                ->clientType('web', 'api')
                ->defaults(['type' => 'main']);
        }, [Router::MIDDLEWARE => ['auth:admin', 'only:granny']]);

        foreach (['index', 'update'] as $action) {
            $this->assertEquals(['auth:admin', 'only:granny'], $router->getByName("addresses.$action")->middlewares);
        }

        $this->assertEquals(['auth:moderator', 'only:granny'], $router->getByName("addresses.edit")->middlewares);

    }

    /**
     * @test
     */
    public function register_with_common_path_and_controller_prefix()
    {

        $router = $this->make();

        $router->register(function (RouteCollector $collector) {

            $collector->get('', 'index')
                ->name('addresses.index')
                ->clientType('web')
                ->middleware('auth');

            $collector->get('/edit', 'edit')
                ->name('addresses.edit')
                ->clientType('api')
                ->middleware('auth');

            $collector->put('/debug', function () {})
                ->name('addresses.debug')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->defaults(['type' => 'main']);

            $collector->put('/{address_id}', 'update')
                ->name('addresses.update')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->defaults(['type' => 'main']);

        }, [Router::PREFIX => 'addresses', Router::CONTROLLER => 'AddressController']);

        $awaited = [
            'index' => '',
            'edit'  => '/edit',
            'update' => '/{address_id}'
        ];

        foreach (['index', 'edit', 'update'] as $action) {
            $pattern = 'addresses'.$awaited[$action];
            $this->assertEquals($pattern, $router->getByName("addresses.$action")->pattern);

            $handler = 'AddressController' . RouteCollector::$methodSeparator . $action;
            $this->assertEquals($handler, $router->getByName("addresses.$action")->handler);
        }

    }

    /**
     * @test
     */
    public function getByPattern_returns_routes_by_pattern()
    {
        $router = $this->make(true);

        $result = $router->getByPattern('users');
        $this->assertContainsOnlyInstancesOf(Route::class, $result);
        $this->assertCount(2, $result);

        $this->assertHasNObjectWith($result, ['pattern' => 'users'], 2);
        $this->assertHasNObjectWith($result, ['methods' => ['GET']], 1);
        $this->assertHasNObjectWith($result, ['methods' => ['POST']], 1);

        $this->assertEquals(['GET'], $router->getByPattern('users', 'GET')[0]->methods);

        $result = $router->getByPattern('users/{user_id}');

        $this->assertCount(3, $result);
        $this->assertHasNObjectWith($result, ['methods' => ['GET']], 1);


        $this->assertCount(0, $router->getByPattern('users/{user_id}/move'));
    }

    /**
     * @test
     */
    public function getByName_returns_routes_by_name()
    {
        $router = $this->make(true);

        foreach (static::$testRoutes as $routeData) {
            $route = $router->getByName($routeData['name']);
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

    protected function make($filled=false)
    {
        $router = new Router();
        if ($filled) {
            $this->fill($router);
        }
        return $router;
    }

    /**
     * @param $url
     * @param string $method
     * @param string $clientType
     * @param string $scope
     *
     * @return Routable
     */
    protected function routable($url, $method=Routable::GET, $clientType=Routable::CLIENT_WEB, $scope='default')
    {
        $routable = new Input();
        if (!$url instanceof UrlContract) {
            $url = new Url($url);
        }
        return $routable->setMethod($method)->setUrl($url)->setClientType($clientType)->setRouteScope($scope);
    }
}

class RouterTest_TestController
{
    public function index()
    {
        return 'index was called: ' . implode(',', func_get_args());
    }

    public function edit()
    {
        return 'edit was called: ' . implode(',', func_get_args());
    }

    public function store()
    {
        return 'update was called: ' . implode(',', func_get_args());
    }
}
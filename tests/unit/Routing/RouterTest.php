<?php
/**
 *  * Created by mtils on 03.07.2022 at 07:28.
 **/

namespace unit\Routing;

use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Argument;
use Ems\Contracts\Routing\Command;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Option;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Contracts\Routing\RouteScope;
use Ems\Core\Lambda;
use Ems\RoutingTrait;
use Ems\TestCase;
use ReflectionException;

use function array_values;
use function func_get_args;
use function implode;
use function is_callable;

class RouterTest extends TestCase
{
    use RoutingTrait;

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(RouterContract::class, $this->router());
    }

    /**
     * @test
     */
    public function it_routes_simple_routes()
    {

        $routes = [
            Route::get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth'),

            Route::get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth'),

            Route::put('addresses/{address}/edit', 'AddressController::update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
        ];

        $router = $this->router($this->dispatcherFactory($routes));

        $routable = $this->routable('addresses');
        $this->assertFalse($routable->isRouted());
        $router->route($routable);

        $this->assertEquals(Input::CLIENT_WEB, $routable->getClientType());
        $this->assertEquals('GET', $routable->getMethod());
        $this->assertEquals('default', (string)$routable->getRouteScope());
        $this->assertInstanceOf(RouteScope::class, $routable->getRouteScope());
        $this->assertInstanceOf(UrlContract::class, $routable->getUrl());
        $this->assertEquals('addresses', (string)$routable->getUrl());
        $this->assertEquals([], $routable->getRouteParameters());

        $route = $routable->getMatchedRoute();

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
    public function it_routes_with_parameters()
    {

        $routes = [
            Route::get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth'),

            Route::get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth'),

            Route::put('addresses/{address}/edit', 'AddressController::update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
        ];

        $router = $this->router($this->dispatcherFactory($routes));

        $routable = $this->routable('addresses/112/edit');
        $router->route($routable);

        $this->assertEquals(Input::CLIENT_WEB, $routable->getClientType());
        $this->assertEquals('GET', $routable->getMethod());
        $this->assertEquals('default', (string)$routable->getRouteScope());
        $this->assertInstanceOf(RouteScope::class, $routable->getRouteScope());
        $this->assertInstanceOf(UrlContract::class, $routable->getUrl());
        $this->assertEquals('addresses/112/edit', (string)$routable->getUrl());
        $this->assertEquals(['address' => 112], $routable->getRouteParameters());

        $route = $routable->getMatchedRoute();

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
    public function it_routes_with_optional_parameters()
    {

        $routes = [

            Route::get('addresses', 'AddressController::index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth'),

            Route::get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth'),

            Route::patch('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('delivery-addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->defaults(['type' => 'main'])
        ];

        $router = $this->router($this->dispatcherFactory($routes));

        $routable = $this->routable('delivery-addresses', 'PATCH');
        $router->route($routable);

        $this->assertEquals(Input::CLIENT_WEB, $routable->getClientType());
        $this->assertEquals('PATCH', $routable->getMethod());
        $this->assertEquals('default', (string)$routable->getRouteScope());
        $this->assertInstanceOf(RouteScope::class, $routable->getRouteScope());
        $this->assertInstanceOf(UrlContract::class, $routable->getUrl());
        $this->assertEquals('delivery-addresses', (string)$routable->getUrl());
        $this->assertEquals(['type' => 'main'], $routable->getRouteParameters());

        $route = $routable->getMatchedRoute();

        $this->assertEquals('AddressController::updateDelivery', $route->handler);
        $this->assertEquals('delivery-addresses.update', $route->name);
        $this->assertEquals(['type' => 'main'], $route->defaults);
        $this->assertEquals(['PATCH'], $route->methods);
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
    public function it_routes_commands()
    {

        $routes = [
            Route::get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth'),

            Route::get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth'),

            Route::console('import:run', 'ImportController::run', function (Command $command) {
                $command->argument('file', 'Import this file (url)')
                        ->argument('email?', 'Send result to this email')
                        ->option('dryrun', 'Do not write changes', 'd')
                        ->option('timeout=5000', 'Kill if too long');
            })
        ];

        $router = $this->router($this->dispatcherFactory($routes));

        $routable = $this->routable('addresses');
        $this->assertFalse($routable->isRouted());
        $router->route($routable);

        $this->assertEquals(Input::CLIENT_WEB, $routable->getClientType());
        $this->assertEquals('GET', $routable->getMethod());
        $this->assertEquals('default', (string)$routable->getRouteScope());
        $this->assertInstanceOf(RouteScope::class, $routable->getRouteScope());
        $this->assertInstanceOf(UrlContract::class, $routable->getUrl());
        $this->assertEquals('addresses', (string)$routable->getUrl());
        $this->assertEquals([], $routable->getRouteParameters());

        $route = $routable->getMatchedRoute();

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

        // According to RFC 3986 Section 3 without an authority no slashes are
        // needed after the scheme so I decided to make the console scheme
        // without slashes
        $routable = $this->routable('console:import:run', Input::CONSOLE, Input::CLIENT_CONSOLE);

        $this->assertFalse($routable->isRouted());
        $router->route($routable);

        $route = $routable->getMatchedRoute();

        $this->assertEquals('ImportController::run', $route->handler);
        $this->assertEquals('import:run', $route->pattern);
        $this->assertEquals('import:run', $route->name);
        $this->assertEquals([], $route->defaults);
        $this->assertEquals([Input::CONSOLE], $route->methods);
        $this->assertEquals([Input::CLIENT_CONSOLE], $route->clientTypes);
        $this->assertTrue($routable->isRouted());

        $command = $route->command;

        $this->assertInstanceOf(Command::class, $command);

        $firstArg = $command->arguments[1];
        $this->assertInstanceOf(Argument::class, $firstArg);
        $this->assertEquals('file', $firstArg->name);
        $this->assertEquals('Import this file (url)', $firstArg->description);
        $this->assertTrue($firstArg->required);
        $this->assertNull($firstArg->default);
        $this->assertEquals('string', $firstArg->type);

        $secondArg = $command->arguments[2];
        $this->assertInstanceOf(Argument::class, $secondArg);
        $this->assertEquals('email', $secondArg->name);
        $this->assertEquals('Send result to this email', $secondArg->description);
        $this->assertFalse($secondArg->required);
        $this->assertNull($secondArg->default);
        $this->assertEquals('string', $secondArg->type);

        $option = $command->options[0];
        $this->assertInstanceOf(Option::class, $option);
        $this->assertEquals('dryrun', $option->name);
        $this->assertEquals('Do not write changes', $option->description);
        $this->assertFalse($option->required);
        $this->assertFalse($option->default);
        $this->assertEquals('bool', $option->type);
        $this->assertEquals('d', $option->shortcut);

        $option = $command->options[1];
        $this->assertInstanceOf(Option::class, $option);
        $this->assertEquals('timeout', $option->name);
        $this->assertEquals('Kill if too long', $option->description);
        $this->assertFalse($option->required);
        $this->assertEquals('5000', $option->default);
        $this->assertEquals('string', $option->type);
        $this->assertEquals('', $option->shortcut);
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_routes_only_for_registered_clientType()
    {

        $routes = [
            Route::get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web')
                ->middleware('auth'),

            Route::get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('api')
                ->middleware('auth'),

            Route::put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('delivery-addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->defaults(['type' => 'main'])
        ];

        $router = $this->router($this->dispatcherFactory($routes));

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

        $routes = [
            Route::get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default')
                ->middleware('auth'),

            Route::get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('admin')
                ->middleware('auth'),

            Route::put('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('delivery-addresses.update')
                ->scope('default', 'admin')
                ->middleware('auth')
                ->defaults(['type' => 'main'])
        ];

        $router = $this->router($this->dispatcherFactory($routes));

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

        $routes = [

            Route::get('addresses', RouterTest_TestController::class.'->index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth'),

            Route::get('addresses/{address}/edit', RouterTest_TestController::class.'->edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth'),

            Route::put('addresses/{address}/edit', RouterTest_TestController::class.'->update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
        ];

        $router = $this->router($this->dispatcherFactory($routes));

        $router->createObjectsBy(function ($class) {
            return new $class;
        });

        $routable = $this->routable('addresses/112/edit');
        $router->route($routable);

        $this->assertEquals(Input::CLIENT_WEB, $routable->getClientType());
        $this->assertEquals('GET', $routable->getMethod());
        $this->assertEquals('default', (string)$routable->getRouteScope());
        $this->assertInstanceOf(RouteScope::class, $routable->getRouteScope());
        $this->assertInstanceOf(UrlContract::class, $routable->getUrl());
        $this->assertEquals('addresses/112/edit', (string)$routable->getUrl());
        $this->assertEquals(['address' => 112], $routable->getRouteParameters());

        $route = $routable->getMatchedRoute();

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
        $this->assertEquals('edit was called: 112' , $handler(...array_values($routable->getRouteParameters())));

    }
}

class RouterTest_Address
{

}

class RouterTest_CustomAddress extends RouterTest_Address
{

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
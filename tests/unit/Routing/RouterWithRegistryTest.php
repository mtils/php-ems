<?php
/**
 *  * Created by mtils on 30.06.19 at 11:12.
 **/

namespace Ems\Routing;


use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Argument;
use Ems\Contracts\Routing\Command;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Option;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Contracts\Routing\RouteScope;
use Ems\Core\Lambda;
use Ems\Core\Url;
use Ems\RoutingTrait;
use Ems\TestCase;
use LogicException;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;

use function array_values;
use function func_get_args;
use function implode;
use function is_callable;
use function iterator_to_array;

class RouterWithRegistryTest extends TestCase
{
    use RoutingTrait;

    #[Test] public function it_implements_interface()
    {
        $this->assertInstanceOf(RouterContract::class, $this->router());
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function it_registers_routes()
    {

        $router = $this->router();
        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

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

        $router->fillDispatchersBy([$registry, 'fillDispatcher']);
        $routes = iterator_to_array($registry);

        $this->assertCount(3, $routes);

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
     * @throws ReflectionException
     */
    #[Test] public function it_registers_routes_with_parameters()
    {

        $registry = $this->registry();
        $router = $this->router();

        $registry->register(function (RouteCollector $collector) {

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

            $collector->post('addresses/{address}/edit', 'AddressController::update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });

        $router->fillDispatchersBy([$registry, 'fillDispatcher']);
        $routes = iterator_to_array($registry);

        $this->assertCount(3, $routes);

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
     * @throws ReflectionException
     */
    #[Test] public function it_registers_routes_with_optional_parameters()
    {

        $registry = $this->registry();
        $router = $this->router();

        $registry->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', 'AddressController::edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->patch('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('delivery-addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->defaults(['type' => 'main']);
        });

        $router->fillDispatchersBy([$registry, 'fillDispatcher']);
        $routes = iterator_to_array($registry);

        $this->assertCount(3, $routes);

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
     * @throws ReflectionException
     */
    #[Test] public function it_registers_commands()
    {

        $registry = $this->registry();
        $router = $this->router();

        $registry->register(function (RouteCollector $collector) {

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

            $collector->command('import:run', 'ImportController::run')
                ->argument('file', 'Import this file (url)')
                ->argument('email?', 'Send result to this email')
                ->option('dryrun', 'Do not write changes', 'd')
                ->option('timeout=5000', 'Kill if too long');


        });

        $router->fillDispatchersBy([$registry, 'fillDispatcher']);
        $routes = iterator_to_array($registry);

        $this->assertCount(3, $routes);

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
     * @throws ReflectionException
     */
    #[Test] public function it_routes_only_for_registered_clientType()
    {

        $registry = $this->registry();
        $router = $this->router();

        $registry->register(function (RouteCollector $collector) {

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

        $router->fillDispatchersBy([$registry, 'fillDispatcher']);

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
     * @throws ReflectionException
     */
    #[Test] public function it_routes_only_for_registered_scope()
    {

        $registry = $this->registry();
        $router = $this->router();

        $registry->register(function (RouteCollector $collector) {

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

        $router->fillDispatchersBy([$registry, 'fillDispatcher']);
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
     * @throws ReflectionException
     */
    #[Test] public function it_handles_routes()
    {

        $router = $this->router();
        $router->createObjectsBy(function ($class) {
            return new $class;
        });

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

            $collector->get('addresses', RouterWithRegistryTest_TestController::class.'->index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', RouterWithRegistryTest_TestController::class.'->edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->put('addresses/{address}/edit', RouterWithRegistryTest_TestController::class.'->update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });

        $routes = iterator_to_array($registry);

        $router->fillDispatchersBy([$registry, 'fillDispatcher']);

        $this->assertCount(3, $routes);

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

        $this->assertEquals(RouterWithRegistryTest_TestController::class . '->edit', $route->handler);
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

    /**
     * @throws ReflectionException
     */
    #[Test] public function it_fails_when_registers_command_and_route_in_one_call_without_collector()
    {
        $command = new Command('addresses:index', 'AddressController::index');
        $this->expectException(LogicException::class);
        $command->get('addresses');
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function it_registers_command_and_route_in_one_call()
    {

        $registry = $this->registry();
        $router = $this->router();

        $registry->register(function (RouteCollector $collector) {

            $collector->command('addresses:index', 'AddressController::index', 'List addresses')
                ->get('addresses')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });

        $routes = iterator_to_array($registry);
        $router->fillDispatchersBy([$registry,'fillDispatcher']);

        $this->assertCount(2, $routes);

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


        $routable = $this->routable('console:addresses:index', Input::CONSOLE, Input::CLIENT_CONSOLE);

        $this->assertFalse($routable->isRouted());
        $router->route($routable);

        $route = $routable->getMatchedRoute();

        $this->assertEquals('AddressController::index', $route->handler);
        $this->assertEquals('addresses:index', $route->pattern);
        $this->assertEquals('addresses:index', $route->name);
        $this->assertEquals([], $route->defaults);
        $this->assertEquals([Input::CONSOLE], $route->methods);
        $this->assertEquals([Input::CLIENT_CONSOLE], $route->clientTypes);
        $this->assertTrue($routable->isRouted());

    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function it_registers_route_and_command_in_one_call()
    {
        $registry = $this->registry();
        $router = $this->router();

        $registry->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->command('addresses:index');
        });

        $routes = iterator_to_array($registry);

        $router->fillDispatchersBy([$registry, 'fillDispatcher']);

        $this->assertCount(2, $routes);

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


        $routable = $this->routable('console:addresses:index', Input::CONSOLE, Input::CLIENT_CONSOLE);

        $this->assertFalse($routable->isRouted());
        $router->route($routable);

        $route = $routable->getMatchedRoute();

        $this->assertEquals('AddressController::index', $route->handler);
        $this->assertEquals('addresses:index', $route->pattern);
        $this->assertEquals('addresses:index', $route->name);
        $this->assertEquals([], $route->defaults);
        $this->assertEquals([Input::CONSOLE], $route->methods);
        $this->assertEquals([Input::CLIENT_CONSOLE], $route->clientTypes);
        $this->assertTrue($routable->isRouted());

    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function it_does_not_call_registrars_when_compiled_and_handles_routes()
    {

        $router = $this->router();
        $router->createObjectsBy(function ($class) {
            return new $class;
        });

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

            $collector->get('addresses', RouterWithRegistryTest_TestController::class.'->index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit', RouterWithRegistryTest_TestController::class.'->edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->put('addresses/{address}/edit', RouterWithRegistryTest_TestController::class.'->update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });


        $compiledData = $registry->compile($this->router());

        $optimized = (new RouteWithRegistryTest_Registry())->setCompiledData($compiledData);

        $router->fillDispatchersBy([$optimized, 'fillDispatcher']);

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

        $this->assertEquals(RouterWithRegistryTest_TestController::class . '->edit', $route->handler);
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

        $this->assertFalse($optimized->wasRegistrarsCalled(), 'The registry should not call registrars when filled by compiled data');

    }

}

class RouterWithRegistryTest_TestController
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

class RouteWithRegistryTest_Registry extends RouteRegistry
{
    public function wasRegistrarsCalled() : bool
    {
        return $this->registrarsCalled;
    }
}
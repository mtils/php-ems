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
        $this->assertInstanceOf(RouterContract::class, $this->router());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_registers_routes()
    {

        $router = $this->router();

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
    public function it_registers_routes_with_parameters()
    {

        $router = $this->router();

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

            $collector->post('addresses/{address}/edit', 'AddressController::update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });

        $routes = iterator_to_array($router);

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
     * @test
     * @throws ReflectionException
     */
    public function it_registers_routes_with_optional_parameters()
    {

        $router = $this->router();

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

            $collector->patch('delivery-addresses[/{type}]', 'AddressController::updateDelivery')
                ->name('delivery-addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->defaults(['type' => 'main']);
        });

        $routes = iterator_to_array($router);

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
     * @test
     * @throws ReflectionException
     */
    public function it_registers_commands()
    {

        $router = $this->router();

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

            $collector->command('import:run', 'ImportController::run')
                ->argument('file', 'Import this file (url)')
                ->argument('email?', 'Send result to this email')
                ->option('dryrun', 'Do not write changes', 'd')
                ->option('timeout=5000', 'Kill if too long');


        });

        $routes = iterator_to_array($router);

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
     * @test
     * @throws ReflectionException
     */
    public function it_routes_only_for_registered_clientType()
    {

        $router = $this->router();

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

        $router = $this->router();

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

        $router = $this->router();
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

    /**
     * @test
     */
    public function getByClientType_returns_only_matching_routes()
    {

        $router = $this->router();

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

        $router = $this->router();

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
            $this->assertEquals(['console'], $router->getByName("addresses.$action", Input::CLIENT_CONSOLE)->clientTypes);
        }
    }

    /**
     * @test
     */
    public function register_with_common_client_type_and_overwrites()
    {

        $router = $this->router();

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
            $this->assertEquals(['console', 'ajax'], $router->getByName("addresses.$action", Input::CLIENT_CONSOLE)->clientTypes);
            $this->assertEquals(['console', 'ajax'], $router->getByName("addresses.$action", Input::CLIENT_AJAX)->clientTypes);
        }
        $this->assertEquals(['api'], $router->getByName("addresses.edit", Input::CLIENT_API)->clientTypes);
    }

    /**
     * @test
     */
    public function register_with_common_scope()
    {

        $router = $this->router();

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

        $mapping = [
            'index' => ['web'],
            'edit'  => ['api'],
            'update' => ['web', 'api']
        ];
        foreach ($mapping as $action=>$clientTypes) {
            foreach ($clientTypes as $clientType) {
                $this->assertEquals(['default', 'admin'], $router->getByName("addresses.$action", $clientType)->scopes);
            }

        }

    }

    /**
     * @test
     */
    public function register_with_common_scope_and_overwrites()
    {

        $router = $this->router();

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

        $mapping = [
            'index' => ['web'],
            'edit'  => ['api'],
            'update' => ['web', 'api']
        ];
        foreach ($mapping as $action=>$clientTypes) {
            if ($action == 'update') {
                continue;
            }
            foreach ($clientTypes as $clientType) {
                $this->assertEquals(['default', 'admin'], $router->getByName("addresses.$action", $clientType)->scopes);
            }

        }

        $this->assertEquals(['master'], $router->getByName("addresses.update", 'web')->scopes);
        $this->assertEquals(['master'], $router->getByName("addresses.update", 'api')->scopes);

    }

    /**
     * @test
     */
    public function register_with_common_middleware()
    {

        $router = $this->router();

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

        $mapping = [
            'index' => ['web'],
            'edit'  => ['api'],
            'update' => ['web', 'api']
        ];
        foreach ($mapping as $action=>$clientTypes) {
            foreach ($clientTypes as $clientType) {
                $this->assertEquals(['auth'], $router->getByName("addresses.$action", $clientType)->middlewares);
            }

        }

    }

    /**
     * @test
     */
    public function register_with_common_middleware_and_additional()
    {

        $router = $this->router();

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

        $mapping = [
            'index' => ['web'],
            'edit'  => ['api'],
            'update' => ['web', 'api']
        ];
        foreach ($mapping as $action=>$clientTypes) {
            if ($action == 'edit') {
                continue;
            }
            foreach ($clientTypes as $clientType) {
                $this->assertEquals(['auth'], $router->getByName("addresses.$action", $clientType)->middlewares);
            }

        }
        $this->assertEquals(['auth', 'has-role:moderator'], $router->getByName("addresses.edit", 'api')->middlewares);

    }

    /**
     * @test
     */
    public function register_with_common_middleware_and_same_middleware_with_different_parameters()
    {

        $router = $this->router();

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

        $this->assertEquals(['auth:moderator', 'only:granny'], $router->getByName("addresses.edit", 'api')->middlewares);

    }

    /**
     * @test
     */
    public function register_with_common_path_and_controller_prefix()
    {

        $router = $this->router();

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
            $clientType = $action == 'edit' ? 'api' : 'web';
            $this->assertEquals($pattern, $router->getByName("addresses.$action", $clientType)->pattern);

            $handler = 'AddressController' . RouteCollector::$methodSeparator . $action;
            $this->assertEquals($handler, $router->getByName("addresses.$action", $clientType)->handler);
        }

    }

    /**
     * @test
     */
    public function getByPattern_returns_routes_by_pattern()
    {
        $router = $this->router(true);

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
        $router = $this->router(true);

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
        $this->router(true)->getByName('foo');
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function clientTypes_returns_all_registered_clientTypes()
    {

        $router = $this->router();
        $router->createObjectsBy(function ($class) {
            return new $class;
        });

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses',
                RouterTest_TestController::class . '->index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->get('addresses/{address}/edit',
                RouterTest_TestController::class . '->edit')
                ->name('addresses.edit')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');

            $collector->put('addresses/{address}/edit',
                RouterTest_TestController::class . '->update')
                ->name('addresses.update')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });

        $routes = iterator_to_array($router);

        $this->assertCount(3, $routes);

        $this->assertEquals(['web', 'api'], $router->clientTypes());


    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_fails_when_registers_command_and_route_in_one_call_without_collector()
    {
        $command = new Command('addresses:index', 'AddressController::index');
        $this->expectException(LogicException::class);
        $command->get('addresses');
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_registers_command_and_route_in_one_call()
    {

        $router = $this->router();

        $router->register(function (RouteCollector $collector) {

            $collector->command('addresses:index', 'AddressController::index', 'List addresses')
                ->get('addresses')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });

        $routes = iterator_to_array($router);

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
     * @test
     * @throws ReflectionException
     */
    public function it_registers_route_and_command_in_one_call()
    {

        $router = $this->router();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->command('addresses:index');
        });

        $routes = iterator_to_array($router);

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
     * @test
     */
    public function it_finds_routes_by_entity_string()
    {
        $router = $this->router();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'UserController::index')
                ->entity(RouterTest_Address::class);
        });

        $route = $router->getByEntityAction(RouterTest_Address::class);
        $this->assertEquals('addresses', $route->pattern);
    }

    /**
     * @test
     */
    public function it_finds_routes_by_direct_class()
    {
        $router = $this->router();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'UserController::index')
                ->entity(RouterTest_Address::class);
        });

        $route = $router->getByEntityAction(new RouterTest_Address());
        $this->assertEquals('addresses', $route->pattern);
    }

    /**
     * @test
     */
    public function it_finds_routes_by_extended_class()
    {
        $router = $this->router();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'UserController::index')
                ->entity(RouterTest_Address::class);
        });

        $route = $router->getByEntityAction(new RouterTest_CustomAddress());
        $this->assertEquals('addresses', $route->pattern);

        $route = $router->getByEntityAction(RouterTest_CustomAddress::class);
        $this->assertEquals('addresses', $route->pattern);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_entity_not_found()
    {
        $router = $this->router();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'UserController::index')
                ->entity(RouterTest_Address::class);
        });

        $this->expectException(OutOfBoundsException::class);
        $router->getByEntityAction('foo');
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_action_not_found()
    {
        $router = $this->router();

        $router->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'UserController::index')
                ->entity(RouterTest_Address::class);
        });

        $route = $router->getByEntityAction(new RouterTest_CustomAddress(), 'index');
        $this->assertEquals('addresses', $route->pattern);

        $this->expectException(OutOfBoundsException::class);
        $router->getByEntityAction(new RouterTest_CustomAddress(), 'show');

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
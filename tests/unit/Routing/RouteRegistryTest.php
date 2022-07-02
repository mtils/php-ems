<?php
/**
 *  * Created by mtils on 19.06.2022 at 07:16.
 **/

namespace Ems\Routing;

use Ems\Contracts\Core\Errors\NotFound;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Command;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Contracts\Routing\RouteScope;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Routing\RouteRegistry;
use Ems\Contracts\Routing\RouteRegistry as RegistryContract;
use Ems\RoutingTrait;
use Ems\TestCase;
use LogicException;
use OutOfBoundsException;
use ReflectionException;

use function is_callable;
use function iterator_to_array;
use function var_dump;

class RouteRegistryTest extends TestCase
{
    use RoutingTrait;

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(RegistryContract::class, $this->registry());
    }

    /**
     * @test
     */
    public function getByClientType_returns_only_matching_routes()
    {

        $registry = $this->registry();

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

        $this->assertEquals('AddressController::index', $registry->getByName('addresses.index')->handler);
        $this->assertEquals('AddressController::updateDelivery', $registry->getByName('delivery-addresses.update')->handler);
        $this->expectException(KeyNotFoundException::class);
        $registry->getByName('addresses.edit');

    }

    /**
     * @test
     */
    public function register_with_common_client_type()
    {

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

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
            $this->assertEquals(['console'], $registry->getByName("addresses.$action", Input::CLIENT_CONSOLE)->clientTypes);
        }
    }

    /**
     * @test
     */
    public function register_with_common_client_type_and_overwrites()
    {

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

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
            $this->assertEquals(['console', 'ajax'], $registry->getByName("addresses.$action", Input::CLIENT_CONSOLE)->clientTypes);
            $this->assertEquals(['console', 'ajax'], $registry->getByName("addresses.$action", Input::CLIENT_AJAX)->clientTypes);
        }
        $this->assertEquals(['api'], $registry->getByName("addresses.edit", Input::CLIENT_API)->clientTypes);
    }

    /**
     * @test
     */
    public function register_with_common_scope()
    {

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

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
        }, [RouterContract::SCOPE => ['default', 'admin']]);

        $mapping = [
            'index' => ['web'],
            'edit'  => ['api'],
            'update' => ['web', 'api']
        ];
        foreach ($mapping as $action=>$clientTypes) {
            foreach ($clientTypes as $clientType) {
                $this->assertEquals(['default', 'admin'], $registry->getByName("addresses.$action", $clientType)->scopes);
            }

        }

    }

    /**
     * @test
     */
    public function register_with_common_scope_and_overwrites()
    {

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

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
        }, [RouterContract::SCOPE => ['default', 'admin']]);

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
                $this->assertEquals(['default', 'admin'], $registry->getByName("addresses.$action", $clientType)->scopes);
            }

        }

        $this->assertEquals(['master'], $registry->getByName("addresses.update", 'web')->scopes);
        $this->assertEquals(['master'], $registry->getByName("addresses.update", 'api')->scopes);

    }

    /**
     * @test
     */
    public function register_with_common_middleware()
    {

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

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
        }, [RouterContract::MIDDLEWARE => 'auth']);

        $mapping = [
            'index' => ['web'],
            'edit'  => ['api'],
            'update' => ['web', 'api']
        ];
        foreach ($mapping as $action=>$clientTypes) {
            foreach ($clientTypes as $clientType) {
                $this->assertEquals(['auth'], $registry->getByName("addresses.$action", $clientType)->middlewares);
            }

        }

    }

    /**
     * @test
     */
    public function register_with_common_middleware_and_additional()
    {

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

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
        }, [RouterContract::MIDDLEWARE => 'auth']);

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
                $this->assertEquals(['auth'], $registry->getByName("addresses.$action", $clientType)->middlewares);
            }

        }
        $this->assertEquals(['auth', 'has-role:moderator'], $registry->getByName("addresses.edit", 'api')->middlewares);

    }

    /**
     * @test
     */
    public function register_with_common_middleware_and_same_middleware_with_different_parameters()
    {

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

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
        }, [RouterContract::MIDDLEWARE => ['auth:admin', 'only:granny']]);

        foreach (['index', 'update'] as $action) {
            $this->assertEquals(['auth:admin', 'only:granny'], $registry->getByName("addresses.$action")->middlewares);
        }

        $this->assertEquals(['auth:moderator', 'only:granny'], $registry->getByName("addresses.edit", 'api')->middlewares);

    }

    /**
     * @test
     */
    public function register_with_common_path_and_controller_prefix()
    {

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

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

        }, [RouterContract::PREFIX => 'addresses', RouterContract::CONTROLLER => 'AddressController']);

        $awaited = [
            'index' => '',
            'edit'  => '/edit',
            'update' => '/{address_id}'
        ];

        foreach (['index', 'edit', 'update'] as $action) {
            $pattern = 'addresses'.$awaited[$action];
            $clientType = $action == 'edit' ? 'api' : 'web';
            $this->assertEquals($pattern, $registry->getByName("addresses.$action", $clientType)->pattern);

            $handler = 'AddressController' . RouteCollector::$methodSeparator . $action;
            $this->assertEquals($handler, $registry->getByName("addresses.$action", $clientType)->handler);
        }

    }

    /**
     * @test
     */
    public function getByPattern_returns_routes_by_pattern()
    {
        $registry = $this->registry(true);

        $result = $registry->getByPattern('users');
        $this->assertContainsOnlyInstancesOf(Route::class, $result);
        $this->assertCount(2, $result);

        $this->assertHasNObjectWith($result, ['pattern' => 'users'], 2);
        $this->assertHasNObjectWith($result, ['methods' => ['GET']], 1);
        $this->assertHasNObjectWith($result, ['methods' => ['POST']], 1);

        $this->assertEquals(['GET'], $registry->getByPattern('users', 'GET')[0]->methods);

        $result = $registry->getByPattern('users/{user_id}');

        $this->assertCount(3, $result);
        $this->assertHasNObjectWith($result, ['methods' => ['GET']], 1);


        $this->assertCount(0, $registry->getByPattern('users/{user_id}/move'));
    }

    /**
     * @test
     */
    public function getByName_returns_routes_by_name()
    {
        $registry = $this->registry(true);

        foreach (static::$testRoutes as $routeData) {
            $route = $registry->getByName($routeData['name']);
            $this->assertInstanceOf(Route::class, $route);
            $this->assertEquals($routeData['name'], $route->name);
        }
    }

    /**
     * @test
     */
    public function getByName_throws_exception_if_route_not_found()
    {
        $this->expectException(NotFound::class);
        $this->registry(true)->getByName('foo');
    }

    /**
     * @test
     */
    public function clientTypes_returns_all_registered_clientTypes()
    {

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

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

        $routes = iterator_to_array($registry);

        $this->assertCount(3, $routes);

        $this->assertEquals(['web', 'api'], $registry->clientTypes());

    }

    /**
     * @test
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

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

            $collector->command('addresses:index', 'AddressController::index', 'List addresses')
                ->get('addresses')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth');
        });

        $routes = iterator_to_array($registry);

        $this->assertCount(2, $routes);

        $commandRoute = $registry->getByPattern('addresses:index', Input::CONSOLE)[0];
        $webRoute = $registry->getByName('addresses.index');

        $this->assertEquals('AddressController::index', $webRoute->handler);
        $this->assertEquals('addresses.index', $webRoute->name);
        $this->assertEquals([], $webRoute->defaults);
        $this->assertEquals(['GET'], $webRoute->methods);
        $this->assertEquals(['web', 'api'], $webRoute->clientTypes);
        $this->assertEquals(['auth'], $webRoute->middlewares);
        $this->assertEquals(['default', 'admin'], $webRoute->scopes);
        $this->assertEquals('addresses', $webRoute->pattern);

        $this->assertEquals('AddressController::index', $commandRoute->handler);
        $this->assertEquals('addresses:index', $commandRoute->pattern);
        $this->assertEquals('addresses:index', $commandRoute->name);
        $this->assertEquals([], $commandRoute->defaults);
        $this->assertEquals([Input::CONSOLE], $commandRoute->methods);
        $this->assertEquals([Input::CLIENT_CONSOLE], $commandRoute->clientTypes);

    }

    /**
     * @test
     */
    public function it_registers_route_and_command_in_one_call()
    {

        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'AddressController::index')
                ->name('addresses.index')
                ->scope('default', 'admin')
                ->clientType('web', 'api')
                ->middleware('auth')
                ->command('addresses:index');
        });

        $routes = iterator_to_array($registry);

        $this->assertCount(2, $routes);

        $consoleRoute = $registry->getByPattern('addresses:index', Input::CONSOLE)[0];
        $webRoute = $registry->getByName('addresses.index');


        $this->assertEquals('AddressController::index', $webRoute->handler);
        $this->assertEquals('addresses.index', $webRoute->name);
        $this->assertEquals([], $webRoute->defaults);
        $this->assertEquals(['GET'], $webRoute->methods);
        $this->assertEquals(['web', 'api'], $webRoute->clientTypes);
        $this->assertEquals(['auth'], $webRoute->middlewares);
        $this->assertEquals(['default', 'admin'], $webRoute->scopes);
        $this->assertEquals('addresses', $webRoute->pattern);


        $this->assertEquals('AddressController::index', $consoleRoute->handler);
        $this->assertEquals('addresses:index', $consoleRoute->pattern);
        $this->assertEquals('addresses:index', $consoleRoute->name);
        $this->assertEquals([], $consoleRoute->defaults);
        $this->assertEquals([Input::CONSOLE], $consoleRoute->methods);
        $this->assertEquals([Input::CLIENT_CONSOLE], $consoleRoute->clientTypes);

    }

    /**
     * @test
     */
    public function it_finds_routes_by_entity_string()
    {
        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'UserController::index')
                ->entity(RegistryTest_Address::class);
        });

        $route = $registry->getByEntityAction(RegistryTest_Address::class);
        $this->assertEquals('addresses', $route->pattern);
    }

    /**
     * @test
     */
    public function it_finds_routes_by_direct_class()
    {
        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'UserController::index')
                ->entity(RegistryTest_Address::class);
        });

        $route = $registry->getByEntityAction(new RegistryTest_Address());
        $this->assertEquals('addresses', $route->pattern);
    }

    /**
     * @test
     */
    public function it_finds_routes_by_extended_class()
    {
        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'UserController::index')
                ->entity(RegistryTest_Address::class);
        });

        $route = $registry->getByEntityAction(new RegistryTest_CustomAddress());
        $this->assertEquals('addresses', $route->pattern);

        $route = $registry->getByEntityAction(RegistryTest_CustomAddress::class);
        $this->assertEquals('addresses', $route->pattern);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_entity_not_found()
    {
        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'UserController::index')
                ->entity(RegistryTest_Address::class);
        });

        $this->expectException(OutOfBoundsException::class);
        $registry->getByEntityAction('foo');
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_action_not_found()
    {
        $registry = $this->registry();

        $registry->register(function (RouteCollector $collector) {

            $collector->get('addresses', 'UserController::index')
                ->entity(RegistryTest_Address::class);
        });

        $route = $registry->getByEntityAction(new RegistryTest_CustomAddress(), 'index');
        $this->assertEquals('addresses', $route->pattern);

        $this->expectException(OutOfBoundsException::class);
        $registry->getByEntityAction(new RegistryTest_CustomAddress(), 'show');

    }

}

class RegistryTest_Address
{

}

class RegistryTest_CustomAddress extends RegistryTest_Address
{

}
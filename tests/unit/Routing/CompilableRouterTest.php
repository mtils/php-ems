<?php
/**
 *  * Created by mtils on 29.05.2022 at 21:36.
 **/

namespace Ems\Routing;

use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\RoutingTrait;
use Ems\TestCase;

use OutOfBoundsException;

use function iterator_to_array;

class CompilableRouterTest extends TestCase
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
     */
    public function compiled_router_skips_normal_router_call()
    {
        $base = $this->router(true);
        $router = $this->make($base);

        $routable = $this->routable('users');
        $routed = $router->route($routable);

        $compiled = $router->compile();

        $this->assertNotEmpty($compiled);

        $emptyRouter = new CompilableRouterTest_Router();
        $router = $this->make($emptyRouter);

        $router->setCompiledData($compiled);

        $this->assertEquals(0, count($emptyRouter->routeCalls));
        $compiledRouted = $router->route($routable);

        $this->assertEquals($routed->getMatchedRoute()->name, $compiledRouted->getMatchedRoute()->name);

        $this->assertCount(0, $emptyRouter->registerCalls);

    }

    /**
     * @test
     */
    public function compiled_router_returns_all_routes()
    {
        $base = $this->router(true);
        $router = $this->make($base);
        $compiled = $router->compile();
        $emptyRouter = new CompilableRouterTest_Router();
        $optimized = $this->make($emptyRouter)->setCompiledData($compiled);

        $baseArray = iterator_to_array($base);
        $optimizedArray = iterator_to_array($optimized);
        foreach ($baseArray as $i=>$route) {
            $this->assertEquals($route->toArray(), $optimizedArray[$i]->toArray());
        }

    }

    /**
     * @test
     */
    public function compiled_router_returns_all_clientTypes()
    {
        $base = $this->router(true);
        $router = $this->make($base);
        $compiled = $router->compile();
        $emptyRouter = new CompilableRouterTest_Router();
        $optimized = $this->make($emptyRouter)->setCompiledData($compiled);

        $this->assertEquals($base->clientTypes(), $optimized->clientTypes());
    }

    /**
     * @test
     */
    public function compiled_router_gets_route_by_pattern()
    {
        $base = $this->router(true);
        $router = $this->make($base);
        $compiled = $router->compile();
        $emptyRouter = new CompilableRouterTest_Router();
        $optimized = $this->make($emptyRouter)->setCompiledData($compiled);

        $this->assertRoutesEquals($base->getByPattern('users/create'), $optimized->getByPattern('users/create'));

        $this->assertRoutesEquals($base->getByPattern('users'), $optimized->getByPattern('users'));

        $this->assertRoutesEquals($base->getByPattern('users', 'GET'), $optimized->getByPattern('users', 'GET'));

        $this->assertRoutesEquals($base->getByPattern('foo'), $optimized->getByPattern('foo'));
    }

    /**
     * @test
     */
    public function compiled_router_returns_by_name()
    {
        $base = $this->router(true);
        $router = $this->make($base);
        $compiled = $router->compile();
        $emptyRouter = new CompilableRouterTest_Router();
        $optimized = $this->make($emptyRouter)->setCompiledData($compiled);

        $this->assertEquals($base->getByName('users.index')->toArray(), $optimized->getByName('users.index')->toArray());

        $this->expectException(KeyNotFoundException::class);
        $optimized->getByName('foo');
    }

    /**
     * @test
     */
    public function compiled_router_returns_by_entity_action()
    {
        $base = $this->router(true);

        $base->register(function (RouteCollector $collector) {
            $collector->get('registrations/create', 'UserController@register')
                ->entity('User', 'register');
        });

        $router = $this->make($base);
        $compiled = $router->compile();
        $emptyRouter = new CompilableRouterTest_Router();
        $optimized = $this->make($emptyRouter)->setCompiledData($compiled);

        $this->assertEquals($base->getByEntityAction('User', 'register')->toArray(), $optimized->getByEntityAction('User', 'register')->toArray());

        $this->assertEquals($compiled, $optimized->getCompiledData());

        $this->expectException(OutOfBoundsException::class);
        $optimized->getByEntityAction('Duck');

    }

    protected function make(RouterContract $router=null) : CompilableRouter
    {
        return new CompilableRouter($router?:$this->router(true));
    }

    /**
     * @param bool $filled
     * @return CompilableRouterTest_Router
     */
    protected function router(bool $filled = false): Router
    {
        $router = new CompilableRouterTest_Router();
        if ($filled) {
            $this->fill($router);
        }
        return $router;
    }

    /**
     * @param Route[] $knownRoutes
     * @param Route[] $routes
     * @return void
     */
    protected function assertRoutesEquals(array $knownRoutes, array $routes)
    {
        foreach ($knownRoutes as $i=>$route) {
            $this->assertEquals($route->toArray(), $routes[$i]->toArray());
        }
    }

}

class CompilableRouterTest_Router extends Router
{
    public $registerCalls = [];
    public $routeCalls = [];

    public function register(callable $registrar, array $attributes = [])
    {
        $this->registerCalls[] = $attributes;
        parent::register($registrar, $attributes);
    }


    public function route(Input $routable): Input
    {
        $this->routeCalls[] = $routable;
        return parent::route($routable); // TODO: Change the autogenerated stub
    }
}
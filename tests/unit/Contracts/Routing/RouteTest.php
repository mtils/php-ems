<?php
/**
 *  * Created by mtils on 22.06.19 at 07:16.
 **/

namespace Ems\Contracts\Routing;


use Ems\Contracts\Core\Arrayable;
use Ems\Contracts\Core\DataObject;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RouteTest extends TestCase
{
    #[Test] public function it_can_be_constructed_without_parameters()
    {
        $this->assertInstanceOf(Route::class, $this->newRoute());
        $this->assertInstanceOf(Arrayable::class, $this->newRoute());
    }

    #[Test] public function method_sets_method_by_string()
    {
        $this->assertEquals(['GET'], $this->newRoute('GET')->methods);
        $this->assertEquals(['PUT'], $this->newRoute('PUT')->methods);
    }

    #[Test] public function method_sets_method_by_array()
    {
        $this->assertEquals(['GET'], $this->newRoute(['GET'])->methods);
        $this->assertEquals(['GET', 'HEAD'], $this->newRoute(['GET', 'HEAD'])->methods);
        $this->assertEquals(['PUT'], $this->newRoute(['PUT'])->methods);
    }

    #[Test] public function pattern_sets_pattern()
    {
        $this->assertEquals('/foo', $this->newRoute('GET', '/foo')->pattern);
    }

    #[Test] public function handler_sets_handler()
    {
        $handler = [static::class, 'index'];
        $this->assertSame($handler, $this->newRoute('GET', '/foo', $handler)->handler);
        $this->assertSame($handler, $this->newRoute('','',$handler)->handler);
    }

    #[Test] public function name_sets_name()
    {
        $this->assertEquals('addresses.index', $this->newRoute()->name('addresses.index')->name);
    }

    #[Test] public function entity_sets_entity_and_action()
    {
        $handler = [static::class, 'index'];
        $route = $this->newRoute('GET', '/users', $handler);
        $this->assertSame('', $route->entity);
        $this->assertSame('', $route->action);

        $route->entity(self::class);
        $this->assertSame(self::class, $route->entity);
        $this->assertSame('index', $route->action);

        $route->entity(DataObject::class, 'destroy');
        $this->assertSame(DataObject::class, $route->entity);
        $this->assertSame('destroy', $route->action);
    }

    #[Test] public function middleware_assigns_middleware_by_string()
    {
        $this->assertEquals(['a'], $this->newRoute()->middleware('a')->middlewares);
        $this->assertEquals(['a', 'b'], $this->newRoute()->middleware('a', 'b')->middlewares);

        $route = $this->newRoute();
        $this->assertEquals(['a'], $route->middleware('a')->middlewares);
        $this->assertEquals(['a', 'b'], $route->middleware('b')->middlewares);

    }

    #[Test] public function middleware_assigns_middleware_by_array()
    {
        $this->assertEquals(['a'], $this->newRoute()->middleware(['a'])->middlewares);
        $this->assertEquals(['a', 'b'], $this->newRoute()->middleware(['a', 'b'])->middlewares);

        $route = $this->newRoute();
        $this->assertEquals(['a'], $route->middleware(['a'])->middlewares);
        $this->assertEquals(['a', 'b', 'c'], $route->middleware(['b', 'c'])->middlewares);
        $this->assertEquals(['a', 'b', 'c', 'd', 'e'], $route->middleware(['d', 'e'])->middlewares);
    }

    #[Test] public function middleware_resets_middleware_if_called_without_parameters()
    {
        $route = $this->newRoute()->middleware(['a', 'b']);
        $this->assertEquals(['a', 'b'], $route->middlewares);
        $this->assertSame($route, $route->middleware());
        $this->assertEquals([], $route->middlewares);
    }

    #[Test] public function clientType_sets_clientTypes_by_string()
    {
        $this->assertEquals(['api'], $this->newRoute()->clientType('api')->clientTypes);
        $this->assertEquals(['api', 'web'], $this->newRoute()->clientType('api', 'web')->clientTypes);

    }

    #[Test] public function clientType_sets_clientTypes_by_array()
    {
        $this->assertEquals(['api'], $this->newRoute()->clientType(['api'])->clientTypes);
        $this->assertEquals(['api', 'web'], $this->newRoute()->clientType(['api', 'web'])->clientTypes);

    }

    #[Test] public function scope_sets_scope_by_string()
    {
        $this->assertEquals(['admin'], $this->newRoute()->scope('admin')->scopes);
        $this->assertEquals(['admin', 'cms'], $this->newRoute()->scope('admin', 'cms')->scopes);

    }

    #[Test] public function scope_sets_scope_by_array()
    {
        $this->assertEquals(['admin'], $this->newRoute()->scope(['admin'])->scopes);
        $this->assertEquals(['admin', 'cms'], $this->newRoute()->scope(['admin', 'cms'])->scopes);
    }

    #[Test] public function defaults_sets_all_defaults()
    {
        $defaults = [
            'foo' => 'boo',
            'bar' => 'far'
        ];
        $route = $this->newRoute();
        $this->assertEquals($defaults, $route->defaults($defaults)->defaults);
        $this->assertEquals([], $route->defaults([])->defaults);

    }

    #[Test] public function defaults_sets_one_default_value()
    {
        $defaults = [
            'foo' => 'boo',
            'bar' => 'far'
        ];
        $route = $this->newRoute();
        $this->assertEquals(['foo' => 'boo'], $route->defaults('foo', 'boo')->defaults);
        $this->assertEquals($defaults, $route->defaults('bar', 'far')->defaults);

        $this->assertEquals([], $route->defaults([])->defaults);

    }

    #[Test] public function toArray_returns_data()
    {
        $route = $this->newRoute('GET', '/foo');
        $data = $route->toArray();
        $this->assertEquals('/foo', $data['pattern']);

    }

    /**
     * @param string|array $method
     * @param string       $pattern
     * @param mixed        $handler
     *
     * @return Route
     */
    protected function newRoute($method=[], $pattern='', $handler=null)
    {
        return new Route($method, $pattern, $handler);
    }
}
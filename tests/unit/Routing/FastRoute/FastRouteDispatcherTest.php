<?php
/**
 *  * Created by mtils on 07.07.19 at 06:51.
 **/

namespace Ems\Routing\FastRoute;


use Ems\Contracts\Routing\RouteHit;
use Ems\Routing\CurlyBraceRouteCompiler;
use Ems\TestCase;
use Ems\TestData;
use Ems\Testing\Cheat;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher as FastRouteDispatcherContract;
use PHPUnit\Framework\Attributes\Test;

class FastRouteDispatcherTest extends TestCase
{
    use TestData;

    protected static $testRoutes = [];

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        static::$testRoutes = static::includeDataFile('routing/basic-routes.php');
    }


    #[Test] public function it_implements_interface()
    {
        $this->assertInstanceOf(FastRouteDispatcher::class, $this->make());
    }

    #[Test] public function add_adds_route()
    {
        $dispatcher = $this->make();
        $method = 'GET';
        $pattern = 'users';
        $handler = 'UserController@index';

        $dispatcher->add($method, $pattern, $handler);
        $hit = $dispatcher->match($method, $pattern);
        $this->assertInstanceOf(RouteHit::class, $hit);
        $this->assertEquals($method, $hit->method);
        $this->assertEquals($pattern, $hit->pattern);
        $this->assertEquals($handler, $hit->handler);
        $this->assertEquals([], $hit->parameters);

    }

    #[Test] public function match_throws_exception_if_route_did_not_match()
    {
        $this->expectException(
            \Ems\Contracts\Routing\Exceptions\RouteNotFoundException::class
        );
        $dispatcher = $this->make();
        $dispatcher->match('GET', 'cars');
    }

    #[Test] public function match_throws_exception_if_method_did_not_match()
    {
        $this->expectException(
            \Ems\Contracts\Routing\Exceptions\MethodNotAllowedException::class
        );
        $dispatcher = $this->make();
        $dispatcher->add('GET', 'cars', 'CarController@index');
        $dispatcher->add('POST', 'cars', 'CarController@store');
        $dispatcher->match('PUT', 'cars');
    }

    #[Test] public function match_throws_exception_if_handler_not_wellformed()
    {
        $this->expectException(
            \Ems\Core\Exceptions\DataIntegrityException::class
        );
        $dispatcher = $this->make();
        $fDispatcher = $this->mock(FastRouteDispatcherContract::class);

        Cheat::set($dispatcher, 'dispatcher', $fDispatcher);
        $method = 'GET';
        $uri = 'orders';

        $fDispatcher->shouldReceive('dispatch')
                   ->with($method, $uri)
                   ->andReturn([
                       0 => FastRouteDispatcherContract::FOUND,
                       1 => 'Not what he expect'
                   ]);

        $dispatcher->match($method, $uri);
    }

    #[Test] public function match_various_routes()
    {
        $dispatcher = $this->make();

        foreach (static::$testRoutes as $test) {
            $dispatcher->add($test['method'], $test['pattern'], $test['handler']);
        }

        foreach (static::$testRoutes as $test) {
            $hit = $dispatcher->match($test['method'], $test['uri']);
            $this->assertInstanceOf(RouteHit::class, $hit);
            $this->assertEquals($test['method'], $hit->method);
            $this->assertEquals($test['pattern'], $hit->pattern);
            $this->assertEquals($test['handler'], $hit->handler);
            $this->assertEquals($test['parameters'], $hit->parameters);
            $this->assertEquals($test['uri'], $dispatcher->path($test['pattern'], $test['parameters']));
        }
    }

    #[Test] public function store_and_restore_state_by_toArray_and_fill()
    {
        $dispatcherForRegister = $this->make();

        foreach (static::$testRoutes as $test) {
            $dispatcherForRegister->add($test['method'], $test['pattern'], $test['handler']);
        }

        $dispatcher = $this->make();

        $dispatcher->fill($dispatcherForRegister->toArray());

        foreach (static::$testRoutes as $test) {
            $hit = $dispatcher->match($test['method'], $test['uri']);
            $this->assertInstanceOf(RouteHit::class, $hit);
            $this->assertEquals($test['method'], $hit->method);
            $this->assertEquals($test['pattern'], $hit->pattern);
            $this->assertEquals($test['handler'], $hit->handler);
            $this->assertEquals($test['parameters'], $hit->parameters);
            $this->assertEquals($test['uri'], $dispatcher->path($test['pattern'], $test['parameters']));
        }

    }

    #[Test] public function CharCountBasedDispatcher()
    {

        $dispatcher = $this->make(new DataGenerator\CharCountBased());
        $method = 'GET';
        $pattern = 'users';
        $handler = 'UserController@index';

        $dispatcher->add($method, $pattern, $handler);
        $hit = $dispatcher->match($method, $pattern);
        $this->assertInstanceOf(RouteHit::class, $hit);
        $this->assertEquals($method, $hit->method);
        $this->assertEquals($pattern, $hit->pattern);
        $this->assertEquals($handler, $hit->handler);
        $this->assertEquals([], $hit->parameters);

    }

    #[Test] public function GroupPosBasedDispatcher()
    {

        $dispatcher = $this->make(new DataGenerator\GroupPosBased());
        $method = 'GET';
        $pattern = 'users';
        $handler = 'UserController@index';

        $dispatcher->add($method, $pattern, $handler);
        $hit = $dispatcher->match($method, $pattern);
        $this->assertInstanceOf(RouteHit::class, $hit);
        $this->assertEquals($method, $hit->method);
        $this->assertEquals($pattern, $hit->pattern);
        $this->assertEquals($handler, $hit->handler);
        $this->assertEquals([], $hit->parameters);

    }

    #[Test] public function MarkBasedDispatcher()
    {

        $dispatcher = $this->make(new DataGenerator\MarkBased());
        $method = 'GET';
        $pattern = 'users';
        $handler = 'UserController@index';

        $dispatcher->add($method, $pattern, $handler);
        $hit = $dispatcher->match($method, $pattern);
        $this->assertInstanceOf(RouteHit::class, $hit);
        $this->assertEquals($method, $hit->method);
        $this->assertEquals($pattern, $hit->pattern);
        $this->assertEquals($handler, $hit->handler);
        $this->assertEquals([], $hit->parameters);

    }

    #[Test] public function unknown_DataGenerator_throws_exception()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedUsageException::class
        );

        /** @var DataGenerator $dataGenerator */
        $dataGenerator = $this->mock(DataGenerator::class);
        $dataGenerator->shouldReceive('addRoute');
        $dataGenerator->shouldReceive('getData')->andReturn([]);

        $dispatcher = $this->make($dataGenerator);
        $method = 'GET';
        $pattern = 'users';
        $handler = 'UserController@index';

        $dispatcher->add($method, $pattern, $handler);
        $hit = $dispatcher->match($method, $pattern);
        $this->assertInstanceOf(RouteHit::class, $hit);
        $this->assertEquals($method, $hit->method);
        $this->assertEquals($pattern, $hit->pattern);
        $this->assertEquals($handler, $hit->handler);
        $this->assertEquals([], $hit->parameters);

    }

    protected function make(DataGenerator $dataGenerator=null, CurlyBraceRouteCompiler $compiler=null)
    {
        return new FastRouteDispatcher($dataGenerator, $compiler);
    }
}
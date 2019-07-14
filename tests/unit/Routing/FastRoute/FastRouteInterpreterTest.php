<?php
/**
 *  * Created by mtils on 07.07.19 at 06:51.
 **/

namespace Ems\Routing\FastRoute;


use Ems\Contracts\Routing\Interpreter;
use Ems\Contracts\Routing\RouteHit;
use Ems\Routing\CurlyBraceRouteCompiler;
use Ems\TestCase;
use Ems\TestData;
use Ems\Testing\Cheat;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher;

class FastRouteInterpreterTest extends TestCase
{
    use TestData;

    protected static $testRoutes = [];

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
        $this->assertInstanceOf(Interpreter::class, $this->make());
    }

    /**
     * @test
     */
    public function add_adds_route()
    {
        $interpreter = $this->make();
        $method = 'GET';
        $pattern = 'users';
        $handler = 'UserController@index';

        $interpreter->add($method, $pattern, $handler);
        $hit = $interpreter->match($method, $pattern);
        $this->assertInstanceOf(RouteHit::class, $hit);
        $this->assertEquals($method, $hit->method);
        $this->assertEquals($pattern, $hit->pattern);
        $this->assertEquals($handler, $hit->handler);
        $this->assertEquals([], $hit->parameters);

    }

    /**
     * @test
     * @expectedException \Ems\Contracts\Routing\Exceptions\RouteNotFoundException
     */
    public function match_throws_exception_if_route_did_not_match()
    {
        $interpreter = $this->make();
        $interpreter->match('GET', 'cars');
    }

    /**
     * @test
     * @expectedException \Ems\Contracts\Routing\Exceptions\MethodNotAllowedException
     */
    public function match_throws_exception_if_method_did_not_match()
    {
        $interpreter = $this->make();
        $interpreter->add('GET', 'cars', 'CarController@index');
        $interpreter->add('POST', 'cars', 'CarController@store');
        $interpreter->match('PUT', 'cars');
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\DataIntegrityException
     */
    public function match_throws_exception_if_handler_not_wellformed()
    {
        $interpreter = $this->make();
        $dispatcher = $this->mock(Dispatcher::class);

        Cheat::set($interpreter, 'dispatcher', $dispatcher);
        $method = 'GET';
        $uri = 'orders';

        $dispatcher->shouldReceive('dispatch')
                   ->with($method, $uri)
                   ->andReturn([
                       0 => Dispatcher::FOUND,
                       1 => 'Not what he expect'
                   ]);

        $interpreter->match($method, $uri);
    }

    /**
     * @test
     */
    public function match_various_routes()
    {
        $interpreter = $this->make();

        foreach (static::$testRoutes as $test) {
            $interpreter->add($test['method'], $test['pattern'], $test['handler']);
        }

        foreach (static::$testRoutes as $test) {
            $hit = $interpreter->match($test['method'], $test['uri']);
            $this->assertInstanceOf(RouteHit::class, $hit);
            $this->assertEquals($test['method'], $hit->method);
            $this->assertEquals($test['pattern'], $hit->pattern);
            $this->assertEquals($test['handler'], $hit->handler);
            $this->assertEquals($test['parameters'], $hit->parameters);
            $this->assertEquals($test['uri'], $interpreter->compile($test['pattern'], $test['parameters']));
        }
    }

    /**
     * @test
     */
    public function store_and_restore_state_by_toArray_and_fill()
    {
        $interpreterForRegister = $this->make();

        foreach (static::$testRoutes as $test) {
            $interpreterForRegister->add($test['method'], $test['pattern'], $test['handler']);
        }

        $interpreter = $this->make();

        $interpreter->fill($interpreterForRegister->toArray());

        foreach (static::$testRoutes as $test) {
            $hit = $interpreter->match($test['method'], $test['uri']);
            $this->assertInstanceOf(RouteHit::class, $hit);
            $this->assertEquals($test['method'], $hit->method);
            $this->assertEquals($test['pattern'], $hit->pattern);
            $this->assertEquals($test['handler'], $hit->handler);
            $this->assertEquals($test['parameters'], $hit->parameters);
            $this->assertEquals($test['uri'], $interpreter->compile($test['pattern'], $test['parameters']));
        }

    }

    /**
     * @test
     */
    public function CharCountBasedDispatcher()
    {

        $interpreter = $this->make(new DataGenerator\CharCountBased());
        $method = 'GET';
        $pattern = 'users';
        $handler = 'UserController@index';

        $interpreter->add($method, $pattern, $handler);
        $hit = $interpreter->match($method, $pattern);
        $this->assertInstanceOf(RouteHit::class, $hit);
        $this->assertEquals($method, $hit->method);
        $this->assertEquals($pattern, $hit->pattern);
        $this->assertEquals($handler, $hit->handler);
        $this->assertEquals([], $hit->parameters);

    }

    /**
     * @test
     */
    public function GroupPosBasedDispatcher()
    {

        $interpreter = $this->make(new DataGenerator\GroupPosBased());
        $method = 'GET';
        $pattern = 'users';
        $handler = 'UserController@index';

        $interpreter->add($method, $pattern, $handler);
        $hit = $interpreter->match($method, $pattern);
        $this->assertInstanceOf(RouteHit::class, $hit);
        $this->assertEquals($method, $hit->method);
        $this->assertEquals($pattern, $hit->pattern);
        $this->assertEquals($handler, $hit->handler);
        $this->assertEquals([], $hit->parameters);

    }

    /**
     * @test
     */
    public function MarkBasedDispatcher()
    {

        $interpreter = $this->make(new DataGenerator\MarkBased());
        $method = 'GET';
        $pattern = 'users';
        $handler = 'UserController@index';

        $interpreter->add($method, $pattern, $handler);
        $hit = $interpreter->match($method, $pattern);
        $this->assertInstanceOf(RouteHit::class, $hit);
        $this->assertEquals($method, $hit->method);
        $this->assertEquals($pattern, $hit->pattern);
        $this->assertEquals($handler, $hit->handler);
        $this->assertEquals([], $hit->parameters);

    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\UnsupportedUsageException
     */
    public function unknown_DataGenerator_throws_exception()
    {

        /** @var DataGenerator $dataGenerator */
        $dataGenerator = $this->mock(DataGenerator::class);
        $dataGenerator->shouldReceive('addRoute');
        $dataGenerator->shouldReceive('getData')->andReturn([]);

        $interpreter = $this->make($dataGenerator);
        $method = 'GET';
        $pattern = 'users';
        $handler = 'UserController@index';

        $interpreter->add($method, $pattern, $handler);
        $hit = $interpreter->match($method, $pattern);
        $this->assertInstanceOf(RouteHit::class, $hit);
        $this->assertEquals($method, $hit->method);
        $this->assertEquals($pattern, $hit->pattern);
        $this->assertEquals($handler, $hit->handler);
        $this->assertEquals([], $hit->parameters);

    }

    protected function make(DataGenerator $dataGenerator=null, CurlyBraceRouteCompiler $compiler=null)
    {
        return new FastRouteInterpreter($dataGenerator, $compiler);
    }
}
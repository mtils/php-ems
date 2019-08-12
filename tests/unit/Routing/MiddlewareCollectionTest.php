<?php
/**
 *  * Created by mtils on 06.07.19 at 20:04.
 **/

namespace Ems\Routing;


use function array_slice;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Routing\Routable;
use Ems\Core\Collections\StringList;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\IOCContainer;
use Ems\Core\Response;
use Ems\Core\Url;
use Ems\TestCase;
use Ems\Contracts\Routing\MiddlewareCollection as CollectionContract;
use function func_get_args;
use function func_num_args;
use function print_r;

class MiddlewareCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_instantiates()
    {
        $this->assertInstanceOf(CollectionContract::class, $this->make());
    }

    /**
     * @test
     */
    public function add_adds_middleware()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class);
        $this->assertEquals(MiddlewareCollectionTest_AMiddleware::class, $c['a']);
    }

    /**
     * @test
     */
    public function middleware_makes_middleware()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class);
        $c->add('b', MiddlewareCollectionTest_BMiddleware::class);

        $this->assertEquals(MiddlewareCollectionTest_AMiddleware::class, $c['a']);
        $this->assertEquals(MiddlewareCollectionTest_BMiddleware::class, $c['b']);

        $this->assertInstanceOf(MiddlewareCollectionTest_AMiddleware::class, $c->middleware('a'));
        $this->assertInstanceOf(MiddlewareCollectionTest_BMiddleware::class, $c->middleware('b'));

    }

    /**
     * @test
     */
    public function parameters_returns_assigned_parameters()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, ['a',1]);
        $c->add('b', MiddlewareCollectionTest_BMiddleware::class, ['b', 2]);
        $c->add('c', MiddlewareCollectionTest_BMiddleware::class, 'c');

        $this->assertEquals(MiddlewareCollectionTest_AMiddleware::class, $c['a']);
        $this->assertEquals(MiddlewareCollectionTest_BMiddleware::class, $c['b']);
        $this->assertEquals(MiddlewareCollectionTest_BMiddleware::class, $c['c']);

        $this->assertInstanceOf(MiddlewareCollectionTest_AMiddleware::class, $c->middleware('a'));
        $this->assertInstanceOf(MiddlewareCollectionTest_BMiddleware::class, $c->middleware('b'));

        $this->assertEquals(['a', 1], $c->parameters('a'));
        $this->assertEquals(['b', 2], $c->parameters('b'));
        $this->assertEquals(['c'], $c->parameters('c'));

    }

    /**
     * @test
     */
    public function offsetExists_works()
    {
        $c = $this->make();
        $this->assertFalse(isset($c['a']));
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class);
        $this->assertTrue(isset($c['a']));
        $this->assertFalse(isset($c['b']));
    }

    /**
     * @test
     */
    public function offsetSet_adds_middleware()
    {
        $c = $this->make();
        $c['a'] = MiddlewareCollectionTest_AMiddleware::class;
        $this->assertEquals(MiddlewareCollectionTest_AMiddleware::class, $c['a']);
    }

    /**
     * @test
     */
    public function offsetUnset_clears_everything_added()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, ['a',1]);
        $c->add('b', MiddlewareCollectionTest_BMiddleware::class, ['b', 2]);
        $c->add('c', MiddlewareCollectionTest_BMiddleware::class, 'c');

        $this->assertEquals(MiddlewareCollectionTest_AMiddleware::class, $c['a']);
        $this->assertEquals(MiddlewareCollectionTest_BMiddleware::class, $c['b']);
        $this->assertEquals(MiddlewareCollectionTest_BMiddleware::class, $c['c']);

        $this->assertInstanceOf(MiddlewareCollectionTest_AMiddleware::class, $c->middleware('a'));
        $this->assertInstanceOf(MiddlewareCollectionTest_BMiddleware::class, $c->middleware('b'));

        $this->assertEquals(['a', 1], $c->parameters('a'));
        $this->assertEquals(['b', 2], $c->parameters('b'));
        $this->assertEquals(['c'], $c->parameters('c'));

        $this->assertTrue(isset($c['b']));
        unset($c['b']);
        $this->assertFalse(isset($c['b']));

        try {
            $c->parameters('b');
            $this->fail('Parameters of middleware "b" should not exist anymore');
        } catch (KeyNotFoundException $e) {

        }

    }

    /**
     * @test
     */
    public function add_with_same_name_deletes_original_stuff()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, ['a',1]);
        $c->add('b', MiddlewareCollectionTest_BMiddleware::class, ['b', 2]);
        $c->add('c', MiddlewareCollectionTest_BMiddleware::class, 'c');

        $this->assertEquals(MiddlewareCollectionTest_AMiddleware::class, $c['a']);
        $this->assertEquals(MiddlewareCollectionTest_BMiddleware::class, $c['b']);
        $this->assertEquals(MiddlewareCollectionTest_BMiddleware::class, $c['c']);

        $this->assertInstanceOf(MiddlewareCollectionTest_AMiddleware::class, $c->middleware('a'));
        $this->assertInstanceOf(MiddlewareCollectionTest_BMiddleware::class, $c->middleware('b'));

        $this->assertEquals(['a', 1], $c->parameters('a'));
        $this->assertEquals(['b', 2], $c->parameters('b'));
        $this->assertEquals(['c'], $c->parameters('c'));


        $c->add('b', MiddlewareCollectionTest_AMiddleware::class);
        $this->assertEquals(MiddlewareCollectionTest_AMiddleware::class, $c['b']);
        $this->assertInstanceOf(MiddlewareCollectionTest_AMiddleware::class, $c->middleware('b'));

        $this->assertEquals([], $c->parameters('b'));
    }

    /**
     * @test
     */
    public function clear_all()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, ['a',1]);
        $c->add('b', MiddlewareCollectionTest_BMiddleware::class, ['b', 2]);
        $c->add('c', MiddlewareCollectionTest_BMiddleware::class, 'c');

        $this->assertCount(3, $c);

        $this->assertSame($c, $c->clear());
        $this->assertCount(0, $c);
    }

    /**
     * @test
     */
    public function clear_clears_nothing_if_empty_array_passed()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, ['a',1]);
        $c->add('b', MiddlewareCollectionTest_BMiddleware::class, ['b', 2]);
        $c->add('c', MiddlewareCollectionTest_BMiddleware::class, 'c');

        $this->assertCount(3, $c);

        $this->assertSame($c, $c->clear([]));
        $this->assertCount(3, $c);
    }

    /**
     * @test
     */
    public function clear_clears_passed_keys()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, ['a',1]);
        $c->add('b', MiddlewareCollectionTest_BMiddleware::class, ['b', 2]);
        $c->add('c', MiddlewareCollectionTest_BMiddleware::class, 'c');

        $this->assertCount(3, $c);

        $this->assertSame($c, $c->clear(['b']));
        $this->assertCount(2, $c);
        $this->assertFalse(isset($c['b']));
    }

    /**
     * @test
     */
    public function ordering_middleware_with_before()
    {
        $c = $this->make();
        $c->add('a', 'A-Binding');
        $c->add('b', 'B-Binding');
        $c->add('c', 'C-Binding');
        $c->add('d', 'D-Binding');
        $c->add('e', 'E-Binding');

        $this->assertInstanceOf(StringList::class, $c->keys());
        $this->assertEquals('a b c d e', (string)$c->keys());

        $c->add('f', 'F-Binding')->before('a');

        $this->assertEquals('f a b c d e', (string)$c->keys());

        $c->add('g', 'G-Binding');

        $this->assertEquals('f a b c d e g', (string)$c->keys());

        $c->add('h', 'H-Binding')->before('e');

        $this->assertEquals('f a b c d h e g', (string)$c->keys());

    }

    /**
     * @test
     */
    public function ordering_middleware_with_after()
    {
        $c = $this->make();
        $c->add('a', 'A-Binding');
        $c->add('b', 'B-Binding');
        $c->add('c', 'C-Binding');
        $c->add('d', 'D-Binding');
        $c->add('e', 'E-Binding');

        $this->assertInstanceOf(StringList::class, $c->keys());
        $this->assertEquals('a b c d e', (string)$c->keys());

        $c->add('f', 'F-Binding')->after('a');

        $this->assertEquals('a f b c d e', (string)$c->keys());

        $c->add('g', 'G-Binding');

        $this->assertEquals('a f b c d e g', (string)$c->keys());

        $c->add('h', 'H-Binding')->after('e');

        $this->assertEquals('a f b c d e h g', (string)$c->keys());

        $c->add('i', 'I-Binding')->after('g');

        $this->assertEquals('a f b c d e h g i', (string)$c->keys());

    }

    /**
     * @test
     */
    public function offsetUnset_manipulates_ordering()
    {
        $c = $this->make();
        $c->add('a', 'A-Binding');
        $c->add('b', 'B-Binding');
        $c->add('c', 'C-Binding');
        $c->add('d', 'D-Binding');
        $c->add('e', 'E-Binding');

        $this->assertInstanceOf(StringList::class, $c->keys());
        $this->assertEquals('a b c d e', (string)$c->keys());

        unset($c['d']);

        $this->assertEquals('a b c e', (string)$c->keys());

        $c->add('f', 'F-Binding')->before('a');

        $this->assertEquals('f a b c e', (string)$c->keys());

        unset($c['f']);

        $this->assertEquals('a b c e', (string)$c->keys());

        $c->add('g', 'G-Binding')->after('c');

        $this->assertEquals('a b c g e', (string)$c->keys());

        unset($c['g']);

        $this->assertEquals('a b c e', (string)$c->keys());


    }

    /**
     * @test
     */
    public function toArray()
    {
        $c = $this->make();
        $c->add('a', 'A-Binding');
        $c->add('b', 'B-Binding');
        $c->add('c', 'C-Binding');
        $c->add('d', 'D-Binding');
        $c->add('e', 'E-Binding');

        $this->assertEquals([
            'a' => 'A-Binding',
            'b' => 'B-Binding',
            'c' => 'C-Binding',
            'd' => 'D-Binding',
            'e' => 'E-Binding',
        ], $c->toArray());

    }

    /**
     * @test
     */
    public function getIterator_returns_right_data()
    {
        $c = $this->make();
        $c->add('a', 'A-Binding');
        $c->add('b', 'B-Binding');
        $c->add('c', 'C-Binding');
        $c->add('d', 'D-Binding');
        $c->add('e', 'E-Binding');

        $manual = [];
        foreach ($c as $key=>$value) {
            $manual[$key] = $value;
        }
        $this->assertEquals($c->toArray(), $manual);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function invoke_without_container_utilizes_runner()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'createResponse');
        $c($this->makeInput());
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function invoke_with_ems_container_utilizes_runner()
    {
        $container = new IOCContainer();
        $c = $this->make($container);
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'createResponse');
        $c($this->makeInput());
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function invoke_with_laravel_container_utilizes_runner()
    {
        $container = new \Ems\Core\Laravel\IOCContainer();
        $c = $this->make($container);
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'createResponse');
        $c($this->makeInput());
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function invoke_creates_response()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'createResponse');

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload();

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(b)'], ['b']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(c)'], ['c']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(d,4)'], ['d','4']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(e)'], ['e']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(createResponse)'], ['createResponse']);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function invoke_with_manipulating_middleware()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'createResponse');
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload();

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function invoke_with_multiple_manipulating_middleware()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'createResponse');
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);
        $c->add('h', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 2]);
        $c->add('i', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 3]);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload();

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,2)'], ['modifyResponse',2]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,3)'], ['modifyResponse',3]);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function invoke_with_multiple_manipulating_middleware_if_middleware_was_assigned_before()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);
        $c->add('h', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 2]);
        $c->add('i', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 3]);
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'createResponse');

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload();

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,2)'], ['modifyResponse',2]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,3)'], ['modifyResponse',3]);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function invoke_with_multiple_manipulating_middleware_if_middleware_was_assigned_before_and_after()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);
        $c->add('i', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 3]);
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'createResponse');
        $c->add('h', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 2]);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload();

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,2)'], ['modifyResponse',2]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,3)'], ['modifyResponse',3]);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function invoke_with_multiple_creating_middleware_returns_first_created_response()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'createResponse', 'first');
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, 'createResponse', 'second');
        $c->add('h', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);
        $c->add('i', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 2]);
        $c->add('j', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 3]);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('first', $response['arg']);
        $input = $response->payload();

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,2)'], ['modifyResponse',2]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,3)'], ['modifyResponse',3]);
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\HandlerNotFoundException
     * @throws \ReflectionException
     */
    public function invoke_invoke_without_response_throws_HandlerNotFoundException()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');

        $c($this->makeInput());
    }

    protected function make(callable $instanceResolver=null)
    {
        return new MiddlewareCollection($instanceResolver);
    }

    /**
     * @param string $path
     * @param array  $parameters (optional)
     * @param string $clientType (optional)
     *
     * @return \Ems\Core\Input
     */
    protected function makeInput($path='/foo', array $parameters=[], $clientType=Routable::CLIENT_WEB)
    {
        $input = new \Ems\Core\Input();
        foreach ($parameters as $key=>$value) {
            $input[$key] = $value;
        }
        $input->setUrl(new Url($path));
        return $input->setMethod('GET')->setClientType($clientType);
    }
}

class MiddlewareCollectionTest_AMiddleware
{

    public $args = [];

    public function __invoke(Input $input, callable $next)
    {
        $requestKey = static::class;
        if (func_num_args() > 2) {
            $this->args = array_slice(func_get_args(), 2);
            $requestKey .= '(' . implode(',', $this->args) . ')';
        }
        $input[$requestKey] = $this->args;

        if (isset($this->args[0]) && $this->args[0] == 'createResponse') {
            $response = (new Response())->setPayload($input);
            if (isset($this->args[1])) {
                $response->offsetSet('arg', $this->args[1]);
            }
            return $response;
        }

        if (isset($this->args[0]) && $this->args[0] == 'modifyResponse') {
            $response = $next($input);
            $response->offsetSet($requestKey, $this->args);
            return $response;
        }

        return $next($input);
    }
}

class MiddlewareCollectionTest_BMiddleware extends MiddlewareCollectionTest_AMiddleware
{
    //
}

class MiddlewareCollectionTest_ResponseMiddleware extends MiddlewareCollectionTest_AMiddleware
{

    public $args = [];

    public function __invoke(Input $input, callable $next)
    {
        $response = new Response();
        $response->setPayload($input);
        return $response;
    }
}

class MiddlewareCollectionTest_ResponseManipulatingMiddleware
{

    public $args = [];

    public function __invoke(Input $input, callable $next)
    {
        if (func_num_args() > 2) {
            $this->args = array_slice(func_get_args(), 2);
        }
        $input[static::class] = true;
        /** @var Response $response */
        $response = $next($input);
        $response->offsetSet(static::class, 'was here');

        return $response;
    }
}
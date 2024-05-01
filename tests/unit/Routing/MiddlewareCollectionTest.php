<?php
/**
 *  * Created by mtils on 06.07.19 at 20:04.
 **/

namespace Ems\Routing;


use Ems\Contracts\Core\Message;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\MiddlewareCollection as CollectionContract;
use Ems\Core\Collections\StringList;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\IOCContainer;
use Ems\Core\Response;
use Ems\Core\Url;
use Ems\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use function array_slice;
use function func_get_args;
use function func_num_args;

class MiddlewareCollectionTest extends TestCase
{
    #[Test] public function it_instantiates()
    {
        $this->assertInstanceOf(CollectionContract::class, $this->make());
    }

    #[Test] public function add_adds_middleware()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class);
        $this->assertEquals(MiddlewareCollectionTest_AMiddleware::class, $c['a']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function middleware_makes_middleware()
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
     * @throws ReflectionException
     */
    #[Test] public function parameters_returns_assigned_parameters()
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
     * @throws ReflectionException
     */
    #[Test] public function parameters_returns_assigned_parameters_in_string_format()
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

    #[Test] public function offsetExists_works()
    {
        $c = $this->make();
        $this->assertFalse(isset($c['a']));
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class);
        $this->assertTrue(isset($c['a']));
        $this->assertFalse(isset($c['b']));
    }

    #[Test] public function offsetSet_adds_middleware()
    {
        $c = $this->make();
        $c['a'] = MiddlewareCollectionTest_AMiddleware::class;
        $this->assertEquals(MiddlewareCollectionTest_AMiddleware::class, $c['a']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function offsetUnset_clears_everything_added()
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
     * @throws ReflectionException
     */
    #[Test] public function add_with_same_name_deletes_original_stuff()
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

    #[Test] public function clear_all()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, ['a',1]);
        $c->add('b', MiddlewareCollectionTest_BMiddleware::class, ['b', 2]);
        $c->add('c', MiddlewareCollectionTest_BMiddleware::class, 'c');

        $this->assertCount(3, $c);

        $this->assertSame($c, $c->clear());
        $this->assertCount(0, $c);
    }

    #[Test] public function clear_clears_nothing_if_empty_array_passed()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, ['a',1]);
        $c->add('b', MiddlewareCollectionTest_BMiddleware::class, ['b', 2]);
        $c->add('c', MiddlewareCollectionTest_BMiddleware::class, 'c');

        $this->assertCount(3, $c);

        $this->assertSame($c, $c->clear([]));
        $this->assertCount(3, $c);
    }

    #[Test] public function clear_clears_passed_keys()
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

    #[Test] public function ordering_middleware_with_before()
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

    #[Test] public function ordering_middleware_with_after()
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

    #[Test] public function offsetUnset_manipulates_ordering()
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

    #[Test] public function toArray()
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

    #[Test] public function getIterator_returns_right_data()
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
     * @throws ReflectionException
     */
    #[Test] public function invoke_without_container_utilizes_runner()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);
        $c($this->makeInput());
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_ems_container_utilizes_runner()
    {
        $container = new IOCContainer();
        $c = $this->make($container);
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);
        $c($this->makeInput());
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_laravel_container_utilizes_runner()
    {
        $container = new \Ems\Core\Laravel\IOCContainer();
        $c = $this->make($container);
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);
        $c($this->makeInput());
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_creates_response()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload;

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(b)'], ['b']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(c)'], ['c']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(d,4)'], ['d','4']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(e)'], ['e']);
        $this->assertEquals($input['handle'], 1);
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_manipulating_middleware()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload;

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_multiple_manipulating_middleware()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);
        $c->add('h', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 2]);
        $c->add('i', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 3]);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload;

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,2)'], ['modifyResponse',2]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,3)'], ['modifyResponse',3]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_multiple_manipulating_middleware_if_middleware_was_assigned_before()
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
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload;

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,2)'], ['modifyResponse',2]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,3)'], ['modifyResponse',3]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_multiple_manipulating_middleware_if_middleware_was_assigned_before_and_after()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);
        $c->add('i', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 3]);
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);
        $c->add('h', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 2]);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload;

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,2)'], ['modifyResponse',2]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,3)'], ['modifyResponse',3]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_multiple_creating_middleware_returns_first_created_response()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'createResponse', 'first');
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, 'createResponse', 'second');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);
        $c->add('h', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);
        $c->add('i', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 2]);
        $c->add('j', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 3]);

        $input = $this->makeInput();
        /** @var Response $response */
        $response = $c($input);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('first', $response['arg']);
        $input = $response->payload;



        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,2)'], ['modifyResponse',2]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,3)'], ['modifyResponse',3]);

    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_multiple_creating_middleware_returns_first_created_response_if_next_is_inputHandler()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'modifyResponse', 0);
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, 'createResponse', 'first');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);
        $c->add('h', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);
        $c->add('i', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 2]);
        $c->add('j', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 3]);

        $input = $this->makeInput();
        /** @var Response $response */
        $response = $c($input);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('first', $response['arg']);
        $input = $response->payload;



        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,1)'], ['modifyResponse',1]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,2)'], ['modifyResponse',2]);
        $this->assertEquals($response[MiddlewareCollectionTest_AMiddleware::class.'(modifyResponse,3)'], ['modifyResponse',3]);

    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_multiple_creating_middleware_returns_first_created_response_if_inputHandler_is_last()
    {
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('f', MiddlewareCollectionTest_AMiddleware::class, 'modifyResponse', 0);
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, 'createResponse', 'first');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);

        $input = $this->makeInput();
        /** @var Response $response */
        $response = $c($input);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('first', $response['arg']);
        $input = $response->payload;


        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class . '(a)'], ['a']);


    }

    /**
     *
     * @throws ReflectionException
     */
    #[Test] public function invoke_invoke_without_InputHandler_throws_NoInputHandlerException()
    {
        $this->expectException(
            \Ems\Contracts\Routing\Exceptions\NoInputHandlerException::class
        );
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');

        $c($this->makeInput());
    }

    /**
     *
     * @throws ReflectionException
     */
    #[Test] public function invoke_invoke_with_more_then_one_InputHandler_throws_LogicException()
    {
        $this->expectException(LogicException::class);
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);
        $c->add('handle_double', MiddlewareCollectionTest_InputHandler::class);

        $c($this->makeInput());
    }

    /**
     * @throws ReflectionException
     *
     */
    #[Test] public function invoke_without_a_response_throws_HandlerNotFoundException()
    {
        $this->expectException(
            \Ems\Core\Exceptions\HandlerNotFoundException::class
        );
        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('g', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 1]);
        $c->add('h', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 2]);
        $c->add('i', MiddlewareCollectionTest_AMiddleware::class, ['modifyResponse', 3]);
        $c->add('handle', new MiddlewareCollectionTest_InputHandler('handle-not', false));

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
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_selected_clientType_does_not_call_on_other_clientType()
    {

        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c')->clientType('api');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload;

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(b)'], ['b']);

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(d,4)'], ['d','4']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(e)'], ['e']);
        $this->assertEquals($input['handle'], 1);

        $this->assertFalse(isset($input[MiddlewareCollectionTest_AMiddleware::class.'(c)']));
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_selected_clientType_call_on_matching_clientType()
    {

        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c')->clientType('api');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);

        /** @var Response $response */
        $response = $c($this->makeInput('/foo', [], 'api'));
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload;

        $this->assertEquals(
            ['a'],
            $input[MiddlewareCollectionTest_AMiddleware::class.'(a)']
        );
        $this->assertEquals(
            ['b'],
            $input[MiddlewareCollectionTest_AMiddleware::class.'(b)']
        );
        $this->assertEquals(
            ['c'],
            $input[MiddlewareCollectionTest_AMiddleware::class.'(c)']
        );
        $this->assertEquals(
            ['d','4'],
            $input[MiddlewareCollectionTest_AMiddleware::class.'(d,4)']
        );
        $this->assertEquals(
            ['e'],
            $input[MiddlewareCollectionTest_AMiddleware::class.'(e)']
        );
        $this->assertEquals(1, $input['handle']);

    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_selected_scope_does_not_call_on_other_scope()
    {

        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c')->scope('admin');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);

        /** @var Response $response */
        $response = $c($this->makeInput());
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload;

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(b)'], ['b']);

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(d,4)'], ['d','4']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(e)'], ['e']);
        $this->assertEquals($input['handle'], 1);

        $this->assertFalse(isset($input[MiddlewareCollectionTest_AMiddleware::class.'(c)']));
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function invoke_with_selected_scope_call_on_matching_scope()
    {

        $c = $this->make();
        $c->add('a', MiddlewareCollectionTest_AMiddleware::class, 'a');
        $c->add('b', MiddlewareCollectionTest_AMiddleware::class, 'b');
        $c->add('c', MiddlewareCollectionTest_AMiddleware::class, 'c')->scope('admin');
        $c->add('d', MiddlewareCollectionTest_AMiddleware::class, ['d', 4]);
        $c->add('e', MiddlewareCollectionTest_AMiddleware::class, 'e');
        $c->add('handle', MiddlewareCollectionTest_InputHandler::class);

        /** @var Response $response */
        $response = $c($this->makeInput('/foo', [], 'web')->setRouteScope('admin'));
        $this->assertInstanceOf(Response::class, $response);
        $input = $response->payload;

        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(a)'], ['a']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(b)'], ['b']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(c)'], ['c']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(d,4)'], ['d','4']);
        $this->assertEquals($input[MiddlewareCollectionTest_AMiddleware::class.'(e)'], ['e']);
        $this->assertEquals($input['handle'], 1);

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
     * @return MiddlewareCollectionTest_Input
     */
    protected function makeInput($path='/foo', array $parameters=[], string $clientType=Input::CLIENT_WEB)
    {
        $input = new MiddlewareCollectionTest_Input('GET', new Url($path));
        foreach ($parameters as $key=>$value) {
            $input[$key] = $value;
        }
        return $input->setUrl(new Url($path))->setClientType($clientType);
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

        if ($input instanceof MiddlewareCollectionTest_Input) {
            $input->wares[] = $this->getName();
        }

        $input[$requestKey] = $this->args;

        if (isset($this->args[0]) && $this->args[0] == 'createResponse') {
            $response = new Response($input);
            if (isset($this->args[1])) {
                //$response->offsetSet('arg', $this->args[1]);
                return $response->with('arg', $this->args[1]);
            }
            return $response;
        }

        if (isset($this->args[0]) && $this->args[0] == 'modifyResponse') {
            /** @var Response $response */
            $response = $next($input);
            return $response->with($requestKey, $this->args);
        }

        return $next($input);
    }

    protected function getName()
    {
        if (!$this->args) {
            return 'default';
        }
        if (in_array($this->args[0], ['createResponse', 'modifyResponse'])) {
            return isset($this->args[1]) ? $this->args[1] : 'default';
        }
        return $this->args[0];
    }
}

class MiddlewareCollectionTest_Input extends GenericInput
{
    public $wares = [];
    public function __construct($method=Input::GET, $url = null)
    {
        parent::__construct();
        $this->method = $method;
        $this->url = $url;
    }
}

class MiddlewareCollectionTest_InputHandler implements \Ems\Contracts\Routing\InputHandler
{

    public $args;

    public $name;

    public $respond;

    public function __construct($name='handle', $respond=true)
    {
        $this->name = $name;
        $this->respond = $respond;
    }

    /**
     * Handle the input and return a corresponding
     *
     * @param Input $input
     *
     * @return \Ems\Core\Response
     */
    public function __invoke(Input $input)
    {
        $input[$this->name] = func_num_args();
        $this->args = func_get_args();
        if (!$this->respond) {
            return null;
        }
        return new Response($input);
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
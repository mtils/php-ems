<?php


namespace Ems\Core\Patterns;

use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\Hookable;
use Ems\Testing\LoggingCallable;

class HookableTraitTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            HasMethodHooks::class,
            $this->newHookable()
        );
    }

    public function test_getListeners_returns_before_listener()
    {
        $hookable = $this->newHookable(['get']);

        $listener = function(){};

        $hookable->onBefore('get', $listener);

        $this->assertSame($listener, $hookable->getListeners('get', 'before')[0]);

    }

    public function test_getListeners_returns_after_listener()
    {
        $hookable = $this->newHookable(['get']);

        $listener = function(){};

        $hookable->onAfter('get', $listener);

        $this->assertSame($listener, $hookable->getListeners('get', 'after')[0]);

    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_getListeners_with_unknown_position_throws_exception()
    {
        $hookable = $this->newHookable(['get']);

        $listener = function(){};

        $hookable->onAfter('get', $listener);

        $this->assertSame($listener, $hookable->getListeners('get', 'foo')[0]);

    }

    public function test_getListeners_return_empty_array_if_no_listeners_assigned()
    {
        $hookable = $this->newHookable(['get']);

        $this->assertEquals([], $hookable->getListeners('get', 'before'));

    }

    public function test_call_before_listeners()
    {
        $hookable = $this->newHookable(['get']);

        $listener = new LoggingCallable;

        $hookable->onBefore('get', $listener);

        $hookable->fireBefore('get',[]);

        $hookable->fireBefore('get', ['a']);

        $hookable->fireBefore('get', ['a', 'b']);

        $hookable->fireBefore('get', ['a', 'b', 'c']);

        $hookable->fireBefore('get', ['a', 'b', 'c', 'd']);

        $hookable->fireBefore('get', ['a', 'b', 'c', 'd', 'e']);

        $this->assertCount(6, $listener);

        $this->assertEquals([], $listener->args(0));
        $this->assertEquals(['a'], $listener->args(1));
        $this->assertEquals(['a','b'], $listener->args(2));
        $this->assertEquals(['a','b','c'], $listener->args(3));
        $this->assertEquals(['a','b','c','d'], $listener->args(4));
        $this->assertEquals(['a','b','c','d','e'], $listener->args(5));

    }

    public function test_call_after_listeners()
    {
        $hookable = $this->newHookable(['get']);

        $listener = new LoggingCallable;

        $hookable->onAfter('get', $listener);

        $hookable->fireAfter('get',[]);

        $hookable->fireAfter('get', ['a']);

        $hookable->fireAfter('get', ['a', 'b']);

        $hookable->fireAfter('get', ['a', 'b', 'c']);

        $hookable->fireAfter('get', ['a', 'b', 'c', 'd']);

        $hookable->fireAfter('get', ['a', 'b', 'c', 'd', 'e']);

        $this->assertCount(6, $listener);

        $this->assertEquals([], $listener->args(0));
        $this->assertEquals(['a'], $listener->args(1));
        $this->assertEquals(['a','b'], $listener->args(2));
        $this->assertEquals(['a','b','c'], $listener->args(3));
        $this->assertEquals(['a','b','c','d'], $listener->args(4));
        $this->assertEquals(['a','b','c','d','e'], $listener->args(5));

    }

    public function test_it_calls_multiple_listeners()
    {
        $hookable = $this->newHookable(['get','delete']);

        $listener1 = new LoggingCallable;
        $listener2 = new LoggingCallable;
        $listener3 = new LoggingCallable;
        $listener4 = new LoggingCallable;

        $hookable->onAfter('get', $listener1);
        $hookable->onAfter('get', $listener2);
        $hookable->onAfter('delete', $listener3);
        $hookable->onAfter('delete', $listener4);


        $hookable->fireAfter('get', []);
        $hookable->fireAfter('delete', ['a']);

        $this->assertCount(1, $listener1);
        $this->assertCount(1, $listener2);
        $this->assertCount(1, $listener3);
        $this->assertCount(1, $listener4);

        $this->assertEquals([], $listener1->args(0));
        $this->assertEquals([], $listener2->args(0));
        $this->assertEquals(['a'], $listener3->args(0));
        $this->assertEquals(['a'], $listener4->args(0));

    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_listen_on_unknown_event_throws_exception()
    {
        $hookable = $this->newHookable(['get']);

        $listener = new LoggingCallable;

        $hookable->onAfter('save', $listener);
    }

    public function test_listen_on_unknown_event_throws_no_exception_if_class_does_not_implement_HasMethodHooks()
    {
        $hookable = $this->newHookableWithoutMethodHooks();

        $listener = new LoggingCallable;

        $hookable->onAfter('save', $listener);
    }

    protected function newHookable($hooks=[])
    {
        $object = new WithMethodHooks;
        $object->hooks = $hooks;
        return $object;
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_listen_on_object_event_throws_exception()
    {
        $hookable = $this->newHookableWithoutMethodHooks();

        $listener = new LoggingCallable;

        $hookable->onAfter(new \stdClass, $listener);
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_listen_on_class_like_event_throws_exception()
    {
        $hookable = $this->newHookableWithoutMethodHooks();

        $listener = new LoggingCallable;

        $hookable->onAfter(get_class($this), $listener);
    }

    protected function newHookableWithoutMethodHooks()
    {
        return new WithoutMethodHooks;
    }
}

class WithoutMethodHooks implements Hookable
{
    use HookableTrait;

    public function fireBefore($event, array $args=[])
    {
        return $this->callBeforeListeners($event, $args);
    }

    public function fireAfter($event, array $args=[])
    {
        return $this->callAfterListeners($event, $args);
    }
}

class WithMethodHooks extends WithoutMethodHooks implements HasMethodHooks
{

    public $hooks = [];

    /**
     * Return an array of methodnames which can be hooked via
     * onBefore and onAfter.
     *
     * @return array
     **/
    public function methodHooks()
    {
        return $this->hooks;
    }

}

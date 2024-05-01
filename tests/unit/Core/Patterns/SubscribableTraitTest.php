<?php

namespace Ems\Core\Patterns;

use Ems\Contracts\Core\Subscribable;
use Ems\Testing\LoggingCallable;

class SubscribableTraitTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            Subscribable::class,
            $this->newSubscribable()
        );
    }

    public function test_getListeners_returns_listener()
    {
        $hookable = $this->newSubscribable();

        $listener = function () {};

        $hookable->on('get', $listener);

        $this->assertSame($listener, $hookable->getListeners('get')[0]);
    }

    public function test_getListeners_with_unknown_position_throws_exception()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\UnSupported::class);
        $hookable = $this->newSubscribable();

        $listener = function () {};

        $hookable->on('get', $listener);

        $this->assertSame($listener, $hookable->getListeners('get', 'before')[0]);
    }

    public function test_getListeners_return_empty_array_if_no_listeners_assigned()
    {
        $hookable = $this->newSubscribable();

        $this->assertEquals([], $hookable->getListeners('get'));
    }

    public function test_call_before_listeners()
    {
        $hookable = $this->newSubscribable();

        $listener = new LoggingCallable();

        $hookable->on('get', $listener);

        $hookable->fire('get', []);

        $hookable->fire('get', ['a']);

        $hookable->fire('get', ['a', 'b']);

        $hookable->fire('get', ['a', 'b', 'c']);

        $hookable->fire('get', ['a', 'b', 'c', 'd']);

        $hookable->fire('get', ['a', 'b', 'c', 'd', 'e']);

        $this->assertCount(6, $listener);

        $this->assertEquals([], $listener->args(0));
        $this->assertEquals(['a'], $listener->args(1));
        $this->assertEquals(['a', 'b'], $listener->args(2));
        $this->assertEquals(['a', 'b', 'c'], $listener->args(3));
        $this->assertEquals(['a', 'b', 'c', 'd'], $listener->args(4));
        $this->assertEquals(['a', 'b', 'c', 'd', 'e'], $listener->args(5));
    }

    public function test_listen_on_object_event_throws_exception()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\UnSupported::class);
        $hookable = $this->newSubscribable();

        $listener = new LoggingCallable();

        $hookable->on(new \stdClass(), $listener);
    }

    public function test_listen_on_class_like_event_throws_exception()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\UnSupported::class);
        $hookable = $this->newHookableWithoutMethodHooks();

        $listener = new LoggingCallable();

        $hookable->onAfter(get_class($this), $listener);
    }

    protected function newSubscribable()
    {
        $object = new SubscribableObject();
        return $object;
    }

    protected function newHookableWithoutMethodHooks()
    {
        return new WithoutMethodHooks();
    }
}

class SubscribableObject implements Subscribable
{
    use SubscribableTrait;

    public function fire($event, array $args=[])
    {
        return $this->callOnListeners($event, $args);
    }
}

<?php


namespace Ems\Core;

use Ems\Contracts\Core\Subscribable;
use Ems\Testing\LoggingCallable;

class EventDispatcherTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            Subscribable::class,
            $this->newDispatcher()
        );
    }

    public function test_getListener_returns_all_listener()
    {

        $dispatcher = $this->newDispatcher();
        $listener = function(){};

        $dispatcher->onAll($listener);

        $this->assertSame($listener, $dispatcher->getListeners('*')[0]);

    }

    public function test_fire_calls_onAll_listener()
    {
        $dispatcher = $this->newDispatcher();
        $listener = new LoggingCallable;

        $dispatcher->onAll($listener);

        $dispatcher('some-event','a');
        $dispatcher('some-event',['a','b']);
        $dispatcher('some-event');

        $this->assertEquals(['some-event','a'], $listener->args(0));
        $this->assertEquals(['some-event','a','b'], $listener->args(1));
        $this->assertEquals(['some-event'], $listener->args(2));
    }

    public function test_fire_calls_all_listeners_and_returns_all_return_values()
    {
        $dispatcher = $this->newDispatcher();

        $calls = [];

        $allListener = function ($event, $char) use (&$calls) {

            if ($event != 'foo') {
                $this->fail('Dispatcher fired wrong event name');
            }

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'all';

            return 'a';

        };

        $beforeListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'before';

            return 'b';
        };

        $onListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'on';

            return 'c';
        };

        $afterListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'after';

            return 'd';
        };

        $dispatcher->onAll($allListener);
        $dispatcher->onBefore('foo', $beforeListener);
        $dispatcher->on('foo', $onListener);
        $dispatcher->onAfter('foo', $afterListener);

        $this->assertEquals(['a','b','c','d'], $dispatcher->fire('foo', 'e'));

    }

    public function test_fire_breaks_on_first_not_null_if_halt_is_true()
    {
        $dispatcher = $this->newDispatcher();

        $calls = [];

        $allListener = function ($event, $char) use (&$calls) {

            if ($event != 'foo') {
                $this->fail('Dispatcher fired wrong event name');
            }

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'all';

        };

        $beforeListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'before';

            return 'b';
        };

        $onListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'on';

            return 'c';
        };

        $afterListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'after';

            return 'd';
        };

        $dispatcher->onAll($allListener);
        $dispatcher->onBefore('foo', $beforeListener);
        $dispatcher->on('foo', $onListener);
        $dispatcher->onAfter('foo', $afterListener);

        $this->assertEquals('b', $dispatcher->fire('foo', 'e', true));

    }

    public function test_fire_stops_propagating_if_listener_returns_false()
    {
        $dispatcher = $this->newDispatcher();

        $calls = [];

        $allListener = function ($event, $char) use (&$calls) {

            if ($event != 'foo') {
                $this->fail('Dispatcher fired wrong event name');
            }

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'all';

            return 'a';

        };

        $beforeListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'before';

            return 'b';
        };

        $onListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'on';

            return false;
        };

        $afterListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'after';

            return 'd';
        };

        $dispatcher->onAll($allListener);
        $dispatcher->onBefore('foo', $beforeListener);
        $dispatcher->on('foo', $onListener);
        $dispatcher->onAfter('foo', $afterListener);

        $this->assertEquals(['a','b',false], $dispatcher->fire('foo', 'e'));

    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\Unsupported
     **/
    public function test_getListeners_with_unknown_position_throws_exception()
    {
        $dispatcher = $this->newDispatcher();
        $dispatcher->getListeners('foo', 'bar');
    }

    protected function newDispatcher()
    {
        $object = new EventDispatcher;
        return $object;
    }

}

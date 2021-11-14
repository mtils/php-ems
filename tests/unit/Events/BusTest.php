<?php

namespace Ems\Events;

use Ems\Contracts\Core\Subscribable;
use Ems\Core\Patterns\SubscribableTrait;
use Ems\Testing\LoggingCallable;

use function print_r;

class BusTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            Subscribable::class,
            $this->newBus()
        );
    }

    public function test_getListener_returns_all_listener()
    {
        $dispatcher = $this->newBus();
        $listener = function () {};

        $dispatcher->on('*', $listener);

        $this->assertSame($listener, $dispatcher->getListeners('*')[0]);
    }

    public function test_fire_calls_onAll_listener()
    {
        $dispatcher = $this->newBus();
        $listener = new LoggingCallable();

        $dispatcher->on('*', $listener);

        $dispatcher('some-event', 'a');
        $dispatcher('some-event', ['a', 'b']);
        $dispatcher('some-event');

        $this->assertEquals(['some-event', 'a'], $listener->args(0));
        $this->assertEquals(['some-event', 'a', 'b'], $listener->args(1));
        $this->assertEquals(['some-event'], $listener->args(2));
    }

    public function test_fire_calls_all_listeners_and_returns_all_return_values()
    {
        $dispatcher = $this->newBus();

        $calls = [];

        $beforeAnyListener = function ($event, $char) {

            if ($event != 'foo') {
                $this->fail('Dispatcher fired wrong event name');
            }

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            return 'a';
        };

        $beforeListener = function ($char) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            return 'b';
        };

        $onListener = function ($char) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            return 'c';

        };

        $allListener = function ($event, $char) {

            if ($event != 'foo') {
                $this->fail('Dispatcher fired wrong event name');
            }

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            return 'd';

        };

        $afterListener = function ($char) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            return 'e';
        };

        $afterAnyListener = function ($event, $char) {

            if ($event != 'foo') {
                $this->fail('Dispatcher fired wrong event name');
            }

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            return 'f';

        };

        $dispatcher->onAfter('*', $afterAnyListener);
        $dispatcher->onAfter('foo', $afterListener);
        $dispatcher->on('*', $allListener);
        $dispatcher->on('foo', $onListener);
        $dispatcher->onBefore('foo', $beforeListener);
        $dispatcher->onBefore('*', $beforeAnyListener);

        $this->assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], $dispatcher->fire('foo', 'e'));

        $tests = [
            'a' => ['*', 'before'],
            'b' => ['foo', 'before'],
            'c' => ['foo', ''],
            'd' => ['*', ''],
            'e' => ['foo', 'after'],
            'f' => ['*', 'after'],
        ];

        foreach ($tests as $result=>$args) {

            $listener = $dispatcher->getListeners($args[0], $args[1])[0];

            if ($args[0] == '*') {
                $this->assertEquals($result, $listener('foo', 'e'));
                continue;
            }
            $this->assertEquals($result, $listener('e'));
        }

    }

    public function test_fire_calls_all_listeners_with_event_object()
    {
        $dispatcher = $this->newBus();

        $calls = [];

        $beforeAnyListener = function ($event, $object) {

            if ($event != BusTest_Event::class) {
                $this->fail('Dispatcher fired wrong event name: ' . $event);
            }

            if (!$object instanceof BusTest_Event) {
                $this->fail('Dispatcher fired wrong parameter ' . print_r($object, true));
            }

            return 'a';
        };

        $beforeListener = function ($event) {

            if (!$event instanceof BusTest_Event) {
                $this->fail('Dispatcher did not fire event object');
            }

            return 'b';
        };

        $onListener = function ($event) {

            if (!$event instanceof BusTest_Event) {
                $this->fail('Dispatcher did not fire event object');
            }

            $this->assertEquals(13, $event->number);
            $this->assertEquals('fired', $event->code);

            return 'c';

        };

        $allListener = function ($event, $object) {

            if ($event != BusTest_Event::class) {
                $this->fail('Dispatcher fired wrong event name');
            }

            if (!$object instanceof BusTest_Event) {
                $this->fail('Dispatcher fired wrong parameter');
            }

            return 'd';

        };

        $afterListener = function ($event) {

            if (!$event instanceof BusTest_Event) {
                $this->fail('Dispatcher did not fire event object');
            }

            return 'e';
        };

        $afterAnyListener = function ($event, $object) {

            if ($event != BusTest_Event::class) {
                $this->fail('Dispatcher fired wrong event name');
            }

            if (!$object instanceof BusTest_Event) {
                $this->fail('Dispatcher fired wrong parameter');
            }

            return 'f';

        };

        $dispatcher->onAfter('*', $afterAnyListener);
        $dispatcher->onAfter(BusTest_Event::class, $afterListener);
        $dispatcher->on('*', $allListener);
        $dispatcher->on(BusTest_Event::class, $onListener);
        $dispatcher->onBefore(BusTest_Event::class, $beforeListener);
        $dispatcher->onBefore('*', $beforeAnyListener);

        $event = new BusTest_Event();
        $event->number = 13;
        $event->code = 'fired';

        $this->assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], $dispatcher->fire($event));

        $tests = [
            'a' => ['*', 'before'],
            'b' => [BusTest_Event::class, 'before'],
            'c' => [BusTest_Event::class, ''],
            'd' => ['*', ''],
            'e' => [BusTest_Event::class, 'after'],
            'f' => ['*', 'after'],
        ];

        foreach ($tests as $result=>$args) {

            $listener = $dispatcher->getListeners($args[0], $args[1])[0];

            if ($args[0] == '*') {
                $this->assertEquals($result, $listener(BusTest_Event::class, $event));
                continue;
            }
            $this->assertEquals($result, $listener($event));
        }

    }

    public function test_fire_breaks_on_first_not_null_if_halt_is_true()
    {
        $dispatcher = $this->newBus();

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

        $dispatcher->on('*', $allListener);
        $dispatcher->onBefore('foo', $beforeListener);
        $dispatcher->on('foo', $onListener);
        $dispatcher->onAfter('foo', $afterListener);

        $this->assertEquals('b', $dispatcher->fire('foo', 'e', true));
    }

    public function test_fire_stops_propagating_if_listener_returns_false()
    {
        $dispatcher = $this->newBus();

        $calls = [];

        $beforeListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'before';

            return 'a';
        };

        $onListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'on';

            return 'b';
        };

        $allListener = function ($event, $char) use (&$calls) {

            if ($event != 'foo') {
                $this->fail('Dispatcher fired wrong event name');
            }

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'all';

            return false;

        };

        $afterListener = function ($char) use (&$calls) {

            if ($char != 'e') {
                $this->fail('Dispatcher fired wrong parameter');
            }

            $calls[] = 'after';

            return 'c';
        };

        $dispatcher->on('*', $allListener);
        $dispatcher->onBefore('foo', $beforeListener);
        $dispatcher->on('foo', $onListener);
        $dispatcher->onAfter('foo', $afterListener);

        $this->assertEquals(['a', 'b', false], $dispatcher->fire('foo', 'e'));
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\Unsupported
     **/
    public function test_getListeners_with_unknown_position_throws_exception()
    {
        $dispatcher = $this->newBus();
        $dispatcher->getListeners('foo', 'bar');
    }

    public function test_mark_returns_new_instance()
    {
        $dispatcher = $this->newBus();
        $listener = new LoggingCallable();

        $dispatcher->on('users.updated', $listener);

        $this->assertNotSame($dispatcher, $dispatcher->mark('no-broadcast'));

        $dispatcher->mark('no-broadcast')->fire('users.updated');
    }

    /**
     * @expectedException LogicException
     **/
    public function test_mark_with_explicit_falsed_mark_throws_exception()
    {
        $dispatcher = $this->newBus();

        $dispatcher->mark('!no-broadcast')->fire('users.updated');
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\Unsupported
     **/
    public function test_mark_with_unknown_mark_throws_exception()
    {
        $dispatcher = $this->newBus();

        $dispatcher->mark('foo');
    }

    public function test_mark_with_added_mark_throws_no_exception()
    {
        $dispatcher = $this->newBus()->registerMark('foo');

        $this->assertInstanceOf(BusWithMarkSupport::class, $dispatcher->mark('foo'));
    }

    public function test_wildcard_listen()
    {
        $dispatcher = $this->newBus();
        $listener = new LoggingCallable;
        $allListener = new LoggingCallable;

        $dispatcher->on('users.*', $listener);
        $dispatcher->on('*', $allListener);

        $dispatcher->fire('address.updated', []);

        $this->assertCount(0, $listener);
        $this->assertCount(1, $allListener);
        $this->assertEquals(['address.updated'], $allListener->args());

        $dispatcher->fire('users.updated', ['a']);

        $this->assertCount(1, $listener);
        $this->assertEquals(['a'], $listener->args());
        $this->assertEquals(['users.updated', 'a'], $allListener->args());

    }

    public function test_mark_calls_only_matching_listeners()
    {

        $dispatcher = $this->newBus();
        $listener = new LoggingCallable();
        $markListener = new LoggingCallable();
        $notMarkedListener = new LoggingCallable();

        $dispatcher->on('users.updated', $listener);
        $dispatcher->when('no-broadcast')->onBefore('users.updated', $markListener);
        $dispatcher->when('!no-broadcast')->onAfter('users.updated', $notMarkedListener);

        $dispatcher->mark('no-broadcast')->fire('users.updated');

        $this->assertCount(1, $listener);
        $this->assertCount(1, $markListener);
        $this->assertCount(0, $notMarkedListener);

        $args = ['a', 'b', 'c'];
        $dispatcher->fire('users.updated', $args);

        $this->assertCount(2, $listener);
        $this->assertEquals($args, $listener->args());
        $this->assertCount(1, $markListener);
        $this->assertEquals([], $markListener->args()); // the old ones
        $this->assertCount(1, $notMarkedListener);
        $this->assertEquals($args, $notMarkedListener->args());

    }

    public function test_mark_calls_only_matching_listeners_with_wildcards()
    {

        $dispatcher = $this->newBus();
        $listener = new LoggingCallable();
        $markListener = new LoggingCallable();
        $notMarkedListener = new LoggingCallable();
        $notMatchingListener = new LoggingCallable();
        $allMarkedListener = new LoggingCallable();

        $dispatcher->on('users.updated', $listener);
        $dispatcher->when('no-broadcast')->on('*', $allMarkedListener);
        $dispatcher->when('no-broadcast')->on('*.updated', $markListener);
        $dispatcher->when('!no-broadcast')->on('users.*', $notMarkedListener);
        $dispatcher->when('no-broadcast')->on('orders.*', $notMatchingListener);

        $dispatcher->fire('users.updated');

        $this->assertCount(1, $listener);
        $this->assertEquals([], $listener->args());

        $this->assertCount(0, $allMarkedListener);
        $this->assertCount(0, $markListener);
        $this->assertCount(1, $notMarkedListener);
        $this->assertEquals([], $notMarkedListener->args());
        $this->assertCount(0, $notMatchingListener);

        $dispatcher->fire('users.created', [33]);

        $this->assertCount(1, $listener);

        $this->assertCount(0, $allMarkedListener);
        $this->assertCount(0, $markListener);
        $this->assertCount(2, $notMarkedListener);
        $this->assertEquals([33], $notMarkedListener->args());
        $this->assertCount(0, $notMatchingListener);

        $dispatcher->mark('no-broadcast')->fire('address.updated', [33]);

        $this->assertCount(1, $listener);

        $this->assertCount(1, $allMarkedListener);
        $this->assertEquals(['address.updated', 33], $allMarkedListener->args());

        $this->assertCount(1, $markListener);
        $this->assertEquals([33], $markListener->args());

        $this->assertCount(2, $notMarkedListener);

        $this->assertCount(0, $notMatchingListener);

    }

    public function test_multiple_when_calls()
    {

        $dispatcher = $this->newBus();
        $markListener = new LoggingCallable();
        $notMarkedListener = new LoggingCallable();

        $dispatcher->when('no-broadcast')->when('from-remote')->onBefore('users.updated', $markListener);
        $dispatcher->when(['!no-broadcast', 'from-remote'])->onAfter('users.updated', $notMarkedListener);

        $dispatcher->mark('no-broadcast')->fire('users.updated');

        $this->assertCount(0, $markListener);
        $this->assertCount(0, $notMarkedListener);

        $dispatcher->mark('no-broadcast')->mark('from-remote')->fire('users.updated');

        $this->assertCount(1, $markListener);
        $this->assertCount(0, $notMarkedListener);

        $dispatcher->mark('from-batch')->mark('from-remote')->fire('users.updated');

        $this->assertCount(1, $markListener);
        $this->assertCount(1, $notMarkedListener);

        $dispatcher->mark(['no-broadcast', 'from-remote'])->fire('users.updated');

        $this->assertCount(2, $markListener);
        $this->assertCount(1, $notMarkedListener);
    }

    public function test_strange_call_combination_of_where_and_fire()
    {

        $dispatcher = $this->newBus();
        $markListener = new LoggingCallable();
        $notMarkedListener = new LoggingCallable();

        $dispatcher->when('no-broadcast')->when('from-remote')->on('users.updated', $markListener);
        $dispatcher->when(['!no-broadcast', 'from-remote'])->on('users.updated', $notMarkedListener);

        $dispatcher->when('no-broadcast')->fire('users.updated');

        $this->assertCount(0, $markListener);
        $this->assertCount(0, $notMarkedListener);

        // Lets do something useful with this test and test if only this two
        // callables were added

        $this->assertCount(2, $dispatcher->getListeners('users.updated'));
        $this->assertCount(0, $dispatcher->getListeners('*'));
    }

    public function test_forward_forwards_to_bus()
    {

        $dispatcher = $this->newBus();
        $listener = new LoggingCallable();

        $dispatcher->on('users.updated', $listener);

        $subscribable = new BusTest_Subscribable;

        $subscribable->on('test', $dispatcher->forward('users.updated'));

        $subscribable->trigger('test', ['a', 'b']);

        $this->assertCount(1, $listener);
        $this->assertEquals(['a', 'b'], $listener->args());
    }

    public function test_forward_forwards_to_other_bus()
    {

        $bus = $this->newBus();
        $bus2 = $this->newBus();

        $listener = new LoggingCallable();

        $bus2->on('users.updated', $listener);

        $bus->fire('users.updated');

        $this->assertCount(0, $listener);

        $bus->forward('users.updated')->to($bus2);

        $bus->fire('users.updated', ['a', 'b']);

        $this->assertCount(1, $listener);
        $this->assertEquals(['a', 'b'], $listener->args());
    }

    public function test_forward_forwards_another_bus_to_this_bus()
    {

        $bus = $this->newBus();
        $bus2 = $this->newBus();

        $listener = new LoggingCallable();

        $bus->on('users.updated', $listener);

        $bus2->fire('users.updated');

        $this->assertCount(0, $listener);

        $bus->forward($bus2, 'users.updated')->to('users.updated');

        $bus2->fire('users.updated', ['a', 'b']);

        $this->assertCount(1, $listener);
        $this->assertEquals(['a', 'b'], $listener->args());
    }

    /**
     * @expectedException LogicException
     **/
    public function test_forward_throws_exception_if_two_events_passed()
    {
        $bus = $this->newBus();
        $bus2 = $this->newBus();

        $bus->forward('users.updated', 'orm.created');
    }

    public function test_installFilter_with_callable()
    {

        $dispatcher = $this->newBus();
        $listener = new LoggingCallable();

        $filter = function ($event, $args) {
            return (bool)count($args);
        };

        $dispatcher->on('users.updated', $listener);

        $this->assertFalse($dispatcher->hasFilter());

        $dispatcher->fire('users.updated');

        $this->assertCount(1, $listener);

        $this->assertSame($dispatcher, $dispatcher->installFilter($filter));
        $this->assertTrue($dispatcher->hasFilter());

        $dispatcher->fire('users.updated');

        $this->assertCount(1, $listener);

        $dispatcher->fire('users.updated', [33]);

        $this->assertCount(2, $listener);

    }

    public function test_installFilter_with_wildcard()
    {

        $dispatcher = $this->newBus();
        $listener = new LoggingCallable();

        $dispatcher->on('*', $listener);

        $this->assertFalse($dispatcher->hasFilter());

        $dispatcher->fire('users.updated');

        $this->assertCount(1, $listener);

        $this->assertSame($dispatcher, $dispatcher->installFilter('*.updated'));
        $this->assertTrue($dispatcher->hasFilter());

        $dispatcher->fire('users.updated');

        $this->assertCount(2, $listener);

        $dispatcher->fire('address.updated');

        $this->assertCount(3, $listener);

        $dispatcher->fire('address.created');

        $this->assertCount(3, $listener);

        $dispatcher->uninstallFilter();

        $dispatcher->fire('address.created');

        $this->assertCount(4, $listener);

    }

    public function test_filtered_filters_events()
    {

        $dispatcher = $this->newBus();
        $listener = new LoggingCallable();

        $dispatcher->on('*', $listener);

        $this->assertFalse($dispatcher->hasFilter());

        $dispatcher->fire('users.updated');

        $this->assertCount(1, $listener);

        $result = $dispatcher->filtered('*.created', function () use ($dispatcher) {

            $dispatcher->fire('users.updated');
            $dispatcher->fire('users.created');
            $dispatcher->fire('users.updated');
            $dispatcher->fire('users.deleted');
            return 'bar';
        });

        $this->assertEquals('bar', $result);

        $this->assertCount(2, $listener);

        $dispatcher->fire('users.updated');
        $this->assertCount(3, $listener);

        try {
            $dispatcher->filtered('*.created', function () use ($dispatcher) {
                throw new \Exception;
            });
            $this->fail('The thrown exception inside filtered should be thrown');
        } catch (\Exception $e) {

        }

        // Filter should be uninstalled, even when exceptions occur
        $dispatcher->fire('users.updated');
        $this->assertCount(4, $listener);

    }

    public function test_mute_and_unmute_bus()
    {

        $dispatcher = $this->newBus();
        $listener = new LoggingCallable();

        $dispatcher->on('*', $listener);

        $this->assertFalse($dispatcher->isMuted());

        $dispatcher->fire('users.updated');

        $this->assertCount(1, $listener);

        $this->assertSame($dispatcher, $dispatcher->mute());
        $this->assertTrue($dispatcher->isMuted());

        $dispatcher->fire('users.updated');

        $this->assertCount(1, $listener);

        $dispatcher->fire('address.updated');

        $this->assertCount(1, $listener);

        $dispatcher->fire('address.created');

        $this->assertCount(1, $listener);

        $dispatcher->uninstallFilter();

        $dispatcher->fire('address.created');

        $this->assertCount(1, $listener);

        $this->assertSame($dispatcher, $dispatcher->mute(false));
        $this->assertFalse($dispatcher->isMuted());

        $dispatcher->fire('address.created');

        $this->assertCount(2, $listener);

    }

    public function test_muted_mutes_events()
    {

        $dispatcher = $this->newBus();
        $listener = new LoggingCallable();

        $dispatcher->on('*', $listener);

        $this->assertFalse($dispatcher->hasFilter());

        $dispatcher->fire('users.updated');

        $this->assertCount(1, $listener);

        $result = $dispatcher->muted(function () use ($dispatcher) {

            $dispatcher->fire('users.updated');
            $dispatcher->fire('users.created');
            $dispatcher->fire('users.updated');
            $dispatcher->fire('users.deleted');

            return 'bar';

        });

        $this->assertEquals('bar', $result);

        $this->assertCount(1, $listener);

        $dispatcher->fire('users.updated');

        $this->assertCount(2, $listener);

        try {
            $dispatcher->muted(function () {
                throw new \Exception;
            });
            $this->fail('The thrown exception inside muted should be thrown');
        } catch (\Exception $e) {

        }

        // Filter should be uninstalled, even when exceptions occur
        $dispatcher->fire('users.updated');
        $this->assertCount(3, $listener);

    }


    public function test_addBus_returns_added_bus()
    {

        $bus = $this->newBus();
        $ormBus = $bus->addBus('orm');
        $this->assertNotSame($bus, $ormBus);

    }

    /**
     * @expectedException OverflowException
     **/
    public function test_addBus_twice_with_same_name_throws_exception()
    {
        $bus = $this->newBus();
        $ormBus = $bus->addBus('orm');
        $bus->addBus('orm');
    }

    public function test_removeBus_removes_added_bus()
    {

        $bus = $this->newBus();
        $this->assertFalse($bus->hasBus('orm'));
        $ormBus = $bus->addBus('orm');
        $this->assertTrue($bus->hasBus('orm'));
        $bus->removeBus('orm');
        $this->assertFalse($bus->hasBus('orm'));

    }

    /**
     * @expectedException OutOfBoundsException
     **/
    public function test_remove_unknown_bus_throws_exception()
    {
        $bus = $this->newBus();
        $bus->removeBus('orm');
    }

    public function test_bus_returns_all_busses_on_every_forked_bus()
    {

        $bus = $this->newBus();
        $ormBus = $bus->addBus('orm');
        $cacheBus = $bus->addBus('cache');

        $this->assertSame($ormBus, $bus->bus('orm'));
        $this->assertSame($cacheBus, $bus->bus('cache'));
        $this->assertSame($ormBus, $ormBus->bus('orm'));
        $this->assertSame($ormBus, $cacheBus->bus('orm'));

        $importBus = $cacheBus->addBus('imports');
        $this->assertEquals('imports', $importBus->name());

        $this->assertSame($importBus, $bus->bus('imports'));
        $this->assertSame($importBus, $ormBus->bus('imports'));

        $importBus->removeBus('orm');

        $this->assertFalse($bus->hasBus('orm'));
        $this->assertFalse($cacheBus->hasBus('orm'));

    }

    /**
     * @expectedException LogicException
     **/
    public function test_addBus_named_default_throws_exception()
    {

        $bus = $this->newBus();
        $ormBus = $bus->addBus('default');
    }

    protected function newBus()
    {
        $object = new Bus();
        return $object;
    }
}

class BusTest_Subscribable implements Subscribable
{
    use SubscribableTrait;

    public function trigger($hook, $args)
    {
        $this->callOnListeners($hook, $args);
    }
}

class BusTest_Event
{
    public $number = 0;
    public $code = '';
}
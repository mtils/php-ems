<?php

namespace Ems\Events;

use Ems\Contracts\Core\HasListeners;
use Ems\Contracts\Core\Subscribable;
use Ems\Contracts\Core\Hookable;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Events\EventForward;
use Ems\Testing\LoggingCallable;
use Ems\Core\Patterns\SubscribableTrait;
use Ems\Core\Patterns\HookableTrait;

class EventForwardTest extends \Ems\TestCase
{
    public function test_it_instantiates()
    {
        $this->assertInstanceOf(
            EventForward::class,
            $this->newForward()
        );
    }

    public function test_getSource_returns_assigned_source()
    {
        $bus = $this->newBus();
        $this->assertSame($bus, $this->newForward($bus)->getSource());
    }


    public function test_getTarget_returns_assigned_target()
    {
        $bus = $this->newBus();
        $target = $this->newBus();
        $forward = $this->newForward($bus,'*');
        $forward->to($target, '*');
        $this->assertSame($target, $forward->getTarget());
    }

    /**
     * @expectedException LogicException
     **/
    public function test_invoke_throws_exception_if_no_source_added()
    {
        $bus = $this->newBus();
        $forward = $this->newForward();
        $forward();
    }

    /**
     * @expectedException LogicException
     **/
    public function test_invoke_throws_exception_if_no_source_events_added()
    {
        $bus = $this->newBus();
        $forward = $this->newForward($bus);
        $forward();
    }

    /**
     * @expectedException LogicException
     **/
    public function test_invoke_throws_exception_if_patterns_assigned()
    {
        $bus = $this->newBus();
        $forward = $this->newForward($bus, 'bla.*');
        $forward();
    }

    public function test_buildTargetEventName_returns_right_replacements()
    {

        $forward = $this->newForward();

        $tests = [
            '*' => [
                'event'  => 'users.updated',
                'result' => 'users.updated'
            ],
            'users.updated' => [
                'event'  => 'users.created',
                'result' => 'users.updated'
            ],
            'users.*' => [
                'event'  => 'users.created',
                'result' => 'users.created'
            ],
            'orm-users.*' => [
                'event'  => 'users.created',
                'result' => 'orm-users.created'
            ],
            'orm.*.*' => [
                'event'  => 'orm.users.created',
                'result' => 'orm.users.created'
            ],
            '*.*' => [
                'event'  => 'orm.users.created',
                'result' => 'users.created'
            ],
            '*.object.*' => [
                'event'  => 'orm.users.created',
                'result' => 'orm.object.created'
            ]
        ];

        foreach ($tests as $pattern=>$data) {
            $this->assertEquals($data['result'], $forward->buildTargetEventName($data['event'], $pattern));
        }
    }

    public function test_forward_to_different_name()
    {
        $bus = $this->newBus();
        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;

        $bus->on('users.stored', $listener);
        $bus->on('users.created', $listener2);

        $bus->fire('users.stored', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($bus, 'users.stored')->to('users.created');

        $bus->fire('users.stored', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

    }

    /**
     * @expectedException LogicException
     **/
    public function test_to_with_unknown_event_throws_exception()
    {
        $bus = $this->newBus();
        $forward = $this->newForward();

        $forward->from($bus, 'users.stored')->to('users.created', 38);

    }

    /**
     * @expectedException LogicException
     **/
    public function test_to_without_target_throws_exception()
    {
        $bus = $this->newBus();
        $forward = $this->newForward();

        $forward->to('users.created', 'bla');

    }

    /**
     * @expectedException LogicException
     **/
    public function test_to_without_source_throws_exception()
    {
        $bus = $this->newBus();
        $forward = $this->newForward();

        $forward->to($bus, 'bla');

    }

    /**
     * @expectedException LogicException
     **/
    public function test_patterns_with_non_bus_throws_exception()
    {

        $bus = $this->newBus();
        $subscribable = new EventForwardTest_Subscribable;
        $forward = $this->newForward();

        $forward->from($subscribable, 'bla.*')->to($bus, 'bli.*');

    }

    public function test_forward_to_different_bus()
    {
        $bus = $this->newBus();
        $bus2 = $this->newBus();

        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;

        $bus->on('users.stored', $listener);
        $bus2->on('users.stored', $listener2);

        $bus->fire('users.stored', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($bus, 'users.stored')->to($bus2);

        $bus->fire('users.stored', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

    }

    /**
     * @expectedException Ems\Core\Exceptions\UnsupportedUsageException
     **/
    public function test_forward_a_second_time_throws_exception()
    {
        $bus = $this->newBus();
        $bus2 = $this->newBus();

        $forward = $this->newForward();
        $forward->from($bus, 'users.stored')->to($bus2);
        $forward->from($bus, 'users.stored')->to($bus2);


    }

    public function test_forward_all_different_bus()
    {
        $bus = $this->newBus();
        $bus2 = $this->newBus();

        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;

        $bus->on('users.stored', $listener);
        $bus2->on('users.stored', $listener2);

        $bus->fire('users.stored', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($bus, '*')->to($bus2);

        $bus->fire('users.stored', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

    }


    public function test_forward_to_different_bus_with_differet_name()
    {
        $bus = $this->newBus();
        $bus2 = $this->newBus();

        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;

        $bus->on('users.stored', $listener);
        $bus2->on('users.created', $listener2);

        $bus->fire('users.stored', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($bus, 'users.stored')->to($bus2, 'users.created');

        $bus->fire('users.stored', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

    }

    public function test_forward_subscribable_hook_to_bus()
    {
        $subscribable = new EventForwardTest_Subscribable();
        $bus2 = $this->newBus();

        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;

        $subscribable->on('error', $listener);
        $bus2->on('user-repo.error', $listener2);

        $subscribable->trigger('error', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($subscribable, 'error')->to($bus2, 'user-repo.error');

        $subscribable->trigger('error', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

    }

    public function test_forward_subscribable_hook_to_bus_with_hook_wildcard()
    {
        $subscribable = new EventForwardTest_Subscribable();
        $bus2 = $this->newBus();

        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;

        $subscribable->on('error', $listener);
        $bus2->on('user-repo.error', $listener2);

        $subscribable->trigger('error', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($subscribable, 'error')->to($bus2, 'user-repo.*');

        $subscribable->trigger('error', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

    }

    public function test_forward_HasMethodHooks_hook_to_bus()
    {
        $hookable = new EventForwardTest_Hookable();
        $bus2 = $this->newBus();

        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;

        $hookable->onAfter('error', $listener);
        $bus2->on('user-repo.error', $listener2);

        $hookable->trigger('error', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($hookable, 'error')->to($bus2, 'user-repo.error');

        $hookable->trigger('error', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

    }

    public function test_forward_multiple_HasMethodHooks_hook_to_bus()
    {
        $hookable = new EventForwardTest_Hookable();
        $bus2 = $this->newBus();

        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;

        $hookable->onAfter('error', $listener);
        $bus2->on('user-repo.error', $listener2);

        $hookable->trigger('error', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($hookable, ['info','error'])->to($bus2, 'user-repo.error');

        $hookable->trigger('error', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

        $hookable->trigger('info', [96]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(2, $listener2);
        $this->assertEquals([96], $listener2->args());
    }

    public function test_forward_multiple_HasMethodHooks_hooks_with_wildcard_to_bus()
    {
        $hookable = new EventForwardTest_Hookable();
        $bus2 = $this->newBus();

        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;
        $listener3 = new LoggingCallable;

        $hookable->onAfter('error', $listener);
        $bus2->on('user-repo.error', $listener2);
        $bus2->on('user-repo.info', $listener3);

        $hookable->trigger('error', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($hookable, ['info','error'])->to($bus2, 'user-repo.*');

        $hookable->trigger('error', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

        $hookable->trigger('info', [96]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());
        $this->assertCount(1, $listener3);
        $this->assertEquals([96], $listener3->args());
    }

    public function test_forward_all_HasMethodHooks_hooks_with_wildcard_to_bus()
    {
        $hookable = new EventForwardTest_Hookable();
        $bus2 = $this->newBus();

        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;
        $listener3 = new LoggingCallable;

        $hookable->onAfter('error', $listener);
        $bus2->on('user-repo.error', $listener2);
        $bus2->on('user-repo.info', $listener3);

        $hookable->trigger('error', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($hookable)->to($bus2, 'user-repo.*');

        $hookable->trigger('error', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

        $hookable->trigger('info', [96]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());
        $this->assertCount(1, $listener3);
        $this->assertEquals([96], $listener3->args());
    }

    public function test_forward_to_different_bus_with_shorter_name()
    {
        $bus = $this->newBus();
        $bus2 = $this->newBus();

        $forward = $this->newForward();

        $listener = new LoggingCallable;
        $listener2 = new LoggingCallable;

        $bus->on('orm.users.stored', $listener);
        $bus2->on('users.stored', $listener2);

        $bus->fire('orm.users.stored', [45]);

        $this->assertCount(1, $listener);
        $this->assertEquals([45], $listener->args());
        $this->assertCount(0, $listener2);

        $forward->from($bus, 'orm.*')->to($bus2, '*.*');

        $bus->fire('orm.users.stored', [54]);

        $this->assertCount(2, $listener);
        $this->assertEquals([54], $listener->args());
        $this->assertCount(1, $listener2);
        $this->assertEquals([54], $listener2->args());

    }

    protected function newForward(HasListeners $source=null, $event=null)
    {
        return new EventForward($source, $event);
    }

    protected function newBus()
    {
        $object = new Bus();
        return $object;
    }
}

class EventForwardTest_Subscribable implements Subscribable
{
    use SubscribableTrait;
    
    public function trigger($hook, $args)
    {
        $this->callOnListeners($hook, $args);
    }
}

class EventForwardTest_Hookable implements HasMethodHooks
{
    use HookableTrait;
    
    public function trigger($hook, $args)
    {
        $this->callAfterListeners($hook, $args);
    }
    
    public function methodHooks()
    {
        return ['info', 'warning', 'error'];
    }
}

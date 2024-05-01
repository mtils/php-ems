<?php

namespace Ems\Events;

use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Events\Bus as BusContract;
use Ems\Contracts\Events\Receiver;
use Ems\Contracts\Events\Signal;
use Ems\Contracts\Events\Slot;
use Ems\Contracts\Http\Client as ClientContract;
use Ems\Contracts\Events\Broadcaster;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Url;
use Ems\Http\HttpResponse;
use Ems\Testing\LoggingCallable;


class RESTBroadcasterTest extends \Ems\TestCase
{

    /**
     * @var Url
     */
    protected $url;

    /**
     * @var Url
     */
    protected $remoteUrl;

    public function setUp(): void
    {
        parent::setUp();
        $this->url = new Url('https://web-utils.de/api/v1');
        $this->remoteUrl = new Url('https://tils.org/api/v1');
    }

    public function test_it_instantiates()
    {
        $this->assertInstanceOf(
            Broadcaster::class,
            $this->newBroadcaster()
        );
    }

    public function test_name_apiName_and_apiVersion()
    {
        $this->assertNotEmpty($this->newBroadcaster()->name());
        $this->assertNotEmpty($this->newBroadcaster()->apiName());
        $this->assertNotEmpty($this->newBroadcaster()->apiVersion());
    }

    public function test_add_custom_signalsUrl()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $url = $this->url->append('foo');

        $broadcaster->setSignalsUrl($url);
        $this->assertSame($url, $broadcaster->getSignalsUrl());
        $this->assertEquals(['signalize', 'receive'], $broadcaster->methodHooks());
    }

    public function test_signalsUrl_throws_exception_if_no_url_setted()
    {
        $this->expectException(
            \Ems\Contracts\Core\Errors\ConfigurationError::class
        );
        $broadcaster = $this->newBroadcaster();
        $broadcaster->getSignalsUrl();
    }

    public function test_add_custom_slotsUrl()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $url = $this->url->append('foo');

        $broadcaster->setSlotsUrl($url);
        $this->assertSame($url, $broadcaster->getSlotsUrl());
    }

    public function test_slotsUrl_throws_exception_if_no_url_setted()
    {
        $this->expectException(
            \Ems\Contracts\Core\Errors\ConfigurationError::class
        );
        $broadcaster = $this->newBroadcaster();
        $broadcaster->getSlotsUrl();
    }

    public function test_addSignal_by_parameters()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);
        $this->assertSame($broadcaster->getBaseUrl(), $this->url);

        $event = 'users.updated';
        $parameters = ['id', 'values'];
        $description = 'Get fired when a user was updated';

        $signal = $broadcaster->addSignal($event, $parameters, $description);
        $this->assertInstanceOf(Signal::class, $signal);
        $this->assertEquals($event, $signal->name);
        $this->assertEquals($event, $signal->eventName);
        $this->assertEquals($parameters, $signal->parameters);
        $this->assertEquals($description, $signal->description);
        $this->assertEquals((string)$this->url->append('signals/users.updated'), (string)$signal->url);
    }

    public function test_addSignal_twice_throws_exception()
    {
        $this->expectException(\OverflowException::class);
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $event = 'users.updated';
        $parameters = ['id', 'values'];
        $description = 'Get fired when a user was updated';

        $broadcaster->addSignal($event, $parameters, $description);
        $broadcaster->addSignal($event, $parameters, $description);
    }


    public function test_signals_returns_added_signals()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $signals = $broadcaster->signals();

        foreach ($signals as $signal) {
            $this->assertEquals($tests[$signal->name], $signal->parameters);
        }

    }

    public function test_add_receiver_by_parameters_throws_exception_if_signal_does_not_exist()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\NotFound::class);
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $receiver = $broadcaster->addReceiver('users.updated', $this->remoteUrl->append('slots/user-changed'));
    }

    public function test_add_receiver_by_parameters()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $url = $this->remoteUrl->append('slots/user-changed');

        $receiver = $broadcaster->addReceiver('users.updated', $url, 'My nice receiver');

        $this->assertInstanceOf(Receiver::class, $receiver);
        $this->assertEquals('users.updated', $receiver->getSignalName());
        $this->assertEquals("$url", (string)$receiver->getUrl());
        $this->assertEquals('My nice receiver', $receiver->getName());
    }

    public function test_add_with_same_url_twice_throws_exception()
    {
        $this->expectException(
            \Ems\Contracts\Core\Errors\ConstraintFailure::class
        );
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $url = $this->remoteUrl->append('slots/user-changed');

        $broadcaster->addReceiver('users.updated', $url, 'My nice receiver');
        $broadcaster->addReceiver('users.updated', $url, 'My nice second receiver');

        $url2 = $this->remoteUrl->scheme('http')->append('slots/user-changed');
    }

    public function test_add_with_same_url_and_different_scheme_twice_throws_exception()
    {
        $this->expectException(
            \Ems\Contracts\Core\Errors\ConstraintFailure::class
        );
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $url = $this->remoteUrl->append('slots/user-changed');

        $broadcaster->addReceiver('users.updated', $url, 'My nice receiver');

        $url2 = $url->scheme('http');

        $broadcaster->addReceiver('users.updated', $url2, 'My nice second receiver');
    }

    public function test_add_with_own_broadcaster_url_throws_exception()
    {
        $this->expectException(
            \Ems\Contracts\Core\Errors\ConstraintFailure::class
        );
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $url = $broadcaster->getSlotsUrl()->append('users.updated')->append('calls');

        $broadcaster->addReceiver('users.updated', $url, 'My nice receiver');

    }

    public function test_getReceiver_returns_assigned_receiver()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $url = $this->remoteUrl->append('slots/user-changed');

        $receiver = $broadcaster->addReceiver('users.updated', $url, 'My nice receiver');

        $this->assertSame($receiver, $broadcaster->getReceiver($receiver->getId()));
    }

    public function test_getReceiver_throws_exception_if_not_found()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\NotFound::class);
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $broadcaster->getReceiver('blabla');
    }

    public function test_receivers_returns_added_receivers()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        $receiverData = [
            'users.updated' => [$this->remoteUrl->append('slots/update-user'), $this->remoteUrl->append('slots/update-users')],
            'users.created' => [$this->remoteUrl->append('slots/invalidate-user-cache')]
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        foreach ($receiverData as $event=>$urls) {
            foreach ($urls as $url) {
                $broadcaster->addReceiver($event, $url);
            }
        }

        $hit = false;
        foreach ($broadcaster->receivers('users.updated') as $receiver) {
            $this->assertEquals('users.updated', $receiver->getSignalName());
            $this->assertInstanceOf(UrlContract::class, $receiver->getUrl());
            $hit = true;
        }

        $this->assertTrue($hit, 'receivers() didnt return expected results.');
    }

    public function test_setReceivers()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        $receiverData = [
            'users.updated' => [$this->remoteUrl->append('slots/update-user'), $this->remoteUrl->append('slots/update-users')],
            'users.created' => [$this->remoteUrl->append('slots/invalidate-user-cache')]
        ];


        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $receivers = [];

        foreach ($receiverData as $event=>$urls) {
            foreach ($urls as $url) {
                $receiver = new GenericReceiver($event, $url);
                $receivers[$receiver->getId()] = $receiver;
            }
        }

        $broadcaster->setReceivers($receivers);

        $hit = false;
        foreach ($broadcaster->receivers('users.updated') as $receiver) {
            $this->assertEquals('users.updated', $receiver->getSignalName());
            $this->assertInstanceOf(UrlContract::class, $receiver->getUrl());
            $hit = true;
        }

        $this->assertTrue($hit, 'receivers() didnt return expected results.');
    }

    public function test_removeReceiver()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        $receiverData = [
            'users.updated' => [$this->remoteUrl->append('slots/update-user'), $this->remoteUrl->append('slots/update-users')],
            'users.created' => [$this->remoteUrl->append('slots/invalidate-user-cache')]
        ];


        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $receivers = [];

        foreach ($receiverData as $event=>$urls) {
            foreach ($urls as $url) {
                $receiver = new GenericReceiver($event, $url);
                $receivers[$receiver->getId()] = $receiver;
            }
        }

        $broadcaster->setReceivers($receivers);

        $hasReceiver = function ($signalName, $receiver) use ($broadcaster) {
            foreach ($broadcaster->receivers($signalName) as $addedReceiver) {
                if ($addedReceiver->getId() == $receiver->getId()) {
                    return true;
                }
            }
            return false;
        };

        foreach ($receivers as $receiver) {
            $this->assertTrue($hasReceiver($receiver->getSignalName(), $receiver));
        }

        $first = array_shift($receivers);

        $this->assertTrue($hasReceiver($first->getSignalName(), $first));

        $broadcaster->removeReceiver($first);

        $this->assertFalse($hasReceiver($first->getSignalName(), $first));
    }

    public function test_removeReceiver_throws_exception_if_receiver_not_found()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\NotFound::class);
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $receiver = new GenericReceiver('foo', $this->remoteUrl);
        $broadcaster->removeReceiver($receiver);
    }

    public function test_addSlot_by_parameters()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $event = 'users.updated';
        $parameters = ['id', 'values'];
        $description = 'Get fired when a user was updated';

        $slot = $broadcaster->addSlot($event, $parameters, $description);
        $this->assertInstanceOf(Slot::class, $slot);
        $this->assertEquals($event, $slot->name);
        $this->assertEquals($event, $slot->eventName);
        $this->assertEquals($parameters, $slot->parameters);
        $this->assertEquals($description, $slot->description);
        $this->assertEquals((string)$this->url->append('slots/users.updated'), (string)$slot->url);
    }

    public function test_addSlot_twice_throws_exception()
    {
        $this->expectException(\OverflowException::class);
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $event = 'users.updated';
        $parameters = ['id', 'values'];
        $description = 'Get fired when a user was updated';

        $broadcaster->addSlot($event, $parameters, $description);
        $broadcaster->addSlot($event, $parameters, $description);
    }

    public function test_slots_returns_added_slots()
    {
        $broadcaster = $this->newBroadcaster()->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSlot($event, $parameters);
        }

        $slots = $broadcaster->slots();

        foreach ($slots as $slot) {
            $this->assertEquals($tests[$slot->name], $slot->parameters);
        }

    }

    public function test_fire_event_of_signal_fires_http_requests()
    {
        $bus = $this->newBus();
        $client = $this->mockClient();

        $broadcaster = $this->newBroadcaster($bus, $client)->setBaseUrl($this->url);

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $url = $this->remoteUrl->append('slots/user-changed');

        $receiver = $broadcaster->addReceiver('users.updated', $url, 'My nice receiver');

        $bus->fire('users.created', [34, ['login' => 'wrong-email@gmail.com']]);

        $args = [
            'id' => 34,
            'values' => ['login' => 'right-email@gmail.com']
        ];

        // If it would be fired here, the client mock would fail
        $response = new HttpResponse('blablabla');
        $client->shouldReceive('post')
               ->with($url, ['parameters' => $args], 'application/json')
               ->andReturn($response);

        $bus->fire('users.updated', [$args['id'], $args['values']]);

    }

    public function test_process_fires_event()
    {
        $bus = $this->newBus();
        $client = $this->mockClient();
        $broadcaster = $this->newBroadcaster($bus, $client)->setBaseUrl($this->url);

        $listener = new LoggingCallable();
        $whenListener = new LoggingCallable();

        $tests = [
            'users.updated' => ['id', 'values'],
            'users.created' => ['id', 'values'],
            'email.sent'    => ['user_id', 'to', 'message_id']
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $bus->on('security.logged_in', $listener);
        $bus->when('!from-remote')->on('security.logged_in', $whenListener);

        $broadcaster->addSlot('security.logged_in', ['id', 'datetime']);

        $parameters = [
            'id'       => 33,
            'datetime' => time()
        ];

        $broadcaster->receive('security.logged_in', $parameters);

        $this->assertCount(1, $listener);
        $this->assertEquals(array_values($parameters), $listener->args());
        $this->assertCount(0, $whenListener);

    }

    public function test_process_fires_known_event_does_not_endless_loop()
    {
        $bus = $this->newBus();
        $client = $this->mockClient();
        $broadcaster = $this->newBroadcaster($bus, $client)->setBaseUrl($this->url);

        $listener = new LoggingCallable();
        $whenListener = new LoggingCallable();

        $tests = [
            'security.logged_in' => ['id', 'datetime']
        ];

        foreach ($tests as $event=>$parameters) {
            $broadcaster->addSignal($event, $parameters);
        }

        $bus->on('security.logged_in', $listener);
        $bus->when('!from-remote')->on('security.logged_in', $whenListener);

        $broadcaster->addSlot('security.logged_in', ['id', 'datetime']);

        $parameters = [
            'id'       => 33,
            'datetime' => time()
        ];

        $broadcaster->receive('security.logged_in', $parameters);

        $this->assertCount(1, $listener);
        $this->assertCount(0, $whenListener);

    }

    public function test_receive_unknown_slot_throws_exception()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\NotFound::class);
        $bus = $this->newBus();
        $client = $this->mockClient();
        $broadcaster = $this->newBroadcaster($bus, $client)->setBaseUrl($this->url);

        $broadcaster->receive('security.logged_in', []);

    }

    protected function newBroadcaster(BusContract $bus=null, ClientContract $client=null)
    {
        $bus = $bus ?: $this->newBus();
        $client = $client ?: $this->mockClient();
        return new RESTBroadcaster($bus, $client);
    }

    protected function newBus()
    {
        return new Bus();
    }

    protected function mockClient()
    {
        return $this->mock(ClientContract::class);
    }
}

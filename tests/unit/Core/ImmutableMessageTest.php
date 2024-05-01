<?php
/**
 *  * Created by mtils on 25.12.2021 at 07:33.
 **/

namespace Ems\Core;

use Ems\Contracts\Core\Message;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Ems\TestCase;
use Ems\Contracts\Core\Message as AbstractMessage;
use PHPUnit\Framework\Attributes\Test;

class ImmutableMessageTest extends TestCase
{

    #[Test] public function it_inherits_message()
    {
        $this->assertInstanceOf(AbstractMessage::class, $this->message());
    }

    #[Test] public function offsetSet_throws_exception()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message([Message::POOL_CUSTOM => $attributes]);

        $this->expectException(UnsupportedUsageException::class);
        $message['foo'] = 'baz';

    }

    #[Test] public function offsetUnset_throws_exception()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message([Message::POOL_CUSTOM => $attributes]);

        $this->expectException(UnsupportedUsageException::class);
        unset($message['foo']);

    }

    #[Test] public function with_returns_changed_instance()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message([Message::POOL_CUSTOM => $attributes]);
        $fork = $message->with('foo', 'baz');
        $this->assertNotSame($message, $fork);
        $this->assertSame($message, $fork->previous);
        $this->assertSame($fork, $message->next);

        $this->assertEquals('bar', $message['foo']);
        $this->assertEquals('baz', $fork['foo']);
    }

    #[Test] public function without_returns_changed_instance()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message([Message::POOL_CUSTOM => $attributes]);
        $fork = $message->without('foo');
        $this->assertNotSame($message, $fork);

        $this->assertEquals('bar', $message['foo']);
        $this->assertFalse(isset($fork['foo']));
    }

    #[Test] public function construct_applies_all_attributes()
    {
        $attributes = [
            'type' => Message::TYPE_INPUT,
            'transport' => Message::TRANSPORT_NETWORK,
            'custom'    => ['foo' => 'bar'],
            'envelope'  => ['Content-Type' =>  'application/json'],
            'payload'   => 'blob'
        ];

        $message = $this->message($attributes);

        $this->assertEquals($attributes['type'], $message->type);
        $this->assertEquals($attributes['transport'], $message->transport);
        $this->assertEquals($attributes['custom'], $message->custom);
        $this->assertEquals($attributes['envelope'], $message->envelope);
        $this->assertEquals($attributes['payload'], $message->payload);
    }

    #[Test] public function withType_creates_new_instance_with_changed_type()
    {
        $attributes = [
            'type' => Message::TYPE_INPUT,
            'transport' => Message::TRANSPORT_NETWORK,
            'custom'    => ['foo' => 'bar'],
            'envelope'  => ['Content-Type' =>  'application/json'],
            'payload'   => 'blob'
        ];

        $message = $this->message($attributes);
        $fork = $message->withType(Message::TYPE_OUTPUT);
        $this->assertNotSame($message, $fork);
        $this->assertEquals(Message::TYPE_OUTPUT, $fork->type);
    }

    #[Test] public function withTransport_creates_new_instance_with_changed_transport()
    {
        $attributes = [
            'type' => Message::TYPE_INPUT,
            'transport' => Message::TRANSPORT_NETWORK,
            'custom'    => ['foo' => 'bar'],
            'envelope'  => ['Content-Type' =>  'application/json'],
            'payload'   => 'blob'
        ];

        $message = $this->message($attributes);
        $fork = $message->withTransport(Message::TRANSPORT_IPC);
        $this->assertNotSame($message, $fork);
        $this->assertEquals(Message::TRANSPORT_IPC, $fork->transport);
    }

    #[Test] public function withEnvelope_creates_new_instance_with_changed_envelope()
    {
        $attributes = [
            'type' => Message::TYPE_INPUT,
            'transport' => Message::TRANSPORT_NETWORK,
            'custom'    => ['foo' => 'bar'],
            'envelope'  => ['Content-Type' =>  'application/json'],
            'payload'   => 'blob'
        ];

        $newEnvelope = ['Content-Type' => 'text/html'];

        $message = $this->message($attributes);
        $fork = $message->withEnvelope($newEnvelope);
        $this->assertNotSame($message, $fork);
        $this->assertEquals($newEnvelope, $fork->envelope);
    }

    #[Test] public function withPayload_creates_new_instance_with_changed_payload()
    {
        $attributes = [
            'type' => Message::TYPE_INPUT,
            'transport' => Message::TRANSPORT_NETWORK,
            'custom'    => ['foo' => 'bar'],
            'envelope'  => ['Content-Type' =>  'application/json'],
            'payload'   => 'blob'
        ];

        $message = $this->message($attributes);
        $fork = $message->withPayload('foo');
        $this->assertNotSame($message, $fork);
        $this->assertEquals('foo', $fork->payload);
    }

    protected function message(array $attributes=[]) : ImmutableMessage
    {
        return new ImmutableMessage($attributes);
    }
}
<?php
/**
 *  * Created by mtils on 24.12.2021 at 09:04.
 **/

namespace Ems\Core;

use ArrayAccess;
use Countable;
use Ems\Contracts\Core\Message;
use Ems\Core\Message as CoreMessage;
use Ems\Core\Collections\ItemNotFoundException;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\TestCase;
use IteratorAggregate;

use function json_encode;

class MessageTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_all_interfaces()
    {
        $message = $this->message();
        $this->assertInstanceOf(ArrayAccess::class, $message);
        $this->assertInstanceOf(IteratorAggregate::class, $message);
        $this->assertInstanceOf(Countable::class, $message);
        $this->assertInstanceOf(Message::class, $message);
    }

    /**
     * @test
     */
    public function get_returns_value()
    {
        $message = $this->message(['foo' => 'bar']);
        $this->assertEquals('bar', $message->get('foo'));
    }

    /**
     * @test
     */
    public function get_returns_no_value()
    {
        $message = $this->message();
        $this->assertNull($message->get('foo'));
    }

    /**
     * @test
     */
    public function getOrFail_throws_exception()
    {
        $message = $this->message(['foo' => 'bar']);
        $this->assertEquals('bar', $message->getOrFail('foo'));
        $this->expectException(ItemNotFoundException::class);
        $message->getOrFail('foofoo');
    }

    /**
     * @test
     */
    public function property_read_access()
    {
        $data = [
            'foo' => 'bar'
        ];
        $envelope = ['Content-Type' => 'application/json'];
        $json = json_encode($data);
        $message = $this->message($json, $envelope);
        $message['foo'] = 'bar';
        $this->assertEquals(Message::TYPE_CUSTOM, $message->type);
        $this->assertEquals('bar', $message->custom['foo']);
        $this->assertEquals('application/json', $message->envelope['Content-Type']);
        $this->assertEquals($json, $message->payload);
        $this->assertEquals(Message::TRANSPORT_APP, $message->transport);

        $this->expectException(KeyNotFoundException::class);
        $this->assertTrue($message->foo);

    }

    /**
     * @test
     */
    public function accept_and_ignore_message()
    {
        $message = $this->message();
        $this->assertFalse($message->isAccepted());
        $this->assertFalse($message->isIgnored());
        $this->assertFalse($message->accepted);
        $this->assertFalse($message->ignored);
        $this->assertSame($message, $message->accept());
        $this->assertTrue($message->isAccepted());
        $this->assertFalse($message->isIgnored());

        $this->assertSame($message, $message->ignore());
        $this->assertFalse($message->accepted);
        $this->assertTrue($message->ignored);
        $this->assertFalse($message->isAccepted());
        $this->assertTrue($message->isIgnored());
    }

    /**
     * @test
     */
    public function offsetExist_and_offsetGet()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message($attributes);

        foreach ($attributes as $key=>$value) {
            $this->assertTrue(isset($message[$key]));
            $this->assertEquals($value, $message[$key]);
        }
        $this->assertFalse(isset($message['baz']));

    }

    /**
     * @test
     */
    public function it_is_countable()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $this->assertCount(count($attributes), $this->message($attributes));
    }

    /**
     * @test
     */
    public function it_is_iterable()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];
        $message = $this->message($attributes);
        $copy = [];
        foreach($message as $key=>$value) {
            $copy[$key] = $value;
        }
        $this->assertEquals($attributes, $copy);
    }

    /**
     * @test
     */
    public function offsetSet_sets_custom_value()
    {
        $message = $this->message();
        $this->assertFalse(isset($message['foo']));
        $message['foo'] = 'bar';
        $this->assertTrue(isset($message['foo']));
        $this->assertEquals('bar', $message['foo']);
        $this->assertEquals(['foo'=>'bar'], $message->custom);
        unset($message['foo']);
        $this->assertFalse(isset($message['foo']));
    }

    /**
     * @test
     */
    public function set_sets_custom_value()
    {
        $message = $this->message();
        $this->assertFalse(isset($message['foo']));
        $this->assertSame($message, $message->set('foo', 'bar'));
        $this->assertTrue(isset($message['foo']));
        $this->assertEquals('bar', $message['foo']);
        $this->assertEquals(['foo'=>'bar'], $message->custom);
        unset($message['foo']);
        $this->assertFalse(isset($message['foo']));
    }

    /**
     * @test
     */
    public function property_write_access()
    {
        $data = [
            'foo' => 'bar'
        ];
        $envelope = ['Content-Type' => 'application/json'];
        $json = json_encode($data);
        $message = $this->message($json, $envelope);
        $message['foo'] = 'bar';
        $this->assertEquals(Message::TYPE_CUSTOM, $message->type);
        $this->assertEquals('bar', $message->custom['foo']);
        $this->assertEquals('application/json', $message->envelope['Content-Type']);
        $this->assertEquals($json, $message->payload);
        $this->assertEquals(Message::TRANSPORT_APP, $message->transport);

        $message->type = Message::TYPE_INPUT;
        $this->assertEquals(Message::TYPE_INPUT, $message->type);

        $this->assertFalse($message->accepted);
        $message->accepted = true;
        $this->assertTrue($message->accepted);
        $this->assertFalse($message->ignored);
        $message->ignored = true;
        $this->assertFalse($message->accepted);
        $this->assertTrue($message->ignored);

        $message->transport = Message::TRANSPORT_NETWORK;
        $this->assertEquals(Message::TRANSPORT_NETWORK, $message->transport);

        $message->custom = ['baz' => 'ngulli'];
        $this->assertEquals(['baz' => 'ngulli'], $message->custom);

        $message->envelope = ['X-Greet' => 'Hello'];
        $this->assertEquals(['X-Greet' => 'Hello'], $message->envelope);

        $message->payload = 'Hello';
        $this->assertEquals('Hello', $message->payload);

        $this->expectException(KeyNotFoundException::class);
        $message->bla = 'Blub';
    }

    protected function message($payload=[], array $envelope=[], $type=Message::TYPE_CUSTOM) : CoreMessage
    {
        return new CoreMessage($payload, $envelope, $type);
    }
}
<?php
/**
 *  * Created by mtils on 12.12.2021 at 18:17.
 **/

namespace unit\Routing;

use Ems\Contracts\Core\AbstractMessage;
use Ems\Contracts\Core\Message;
use Ems\Contracts\Routing\TraceableMessage;
use Ems\Core\Collections\ItemNotFoundException;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Ems\Core\Filesystem\StringStream;
use Ems\Core\Url;
use Ems\TestCase;
use Psr\Http\Message\MessageInterface;

use Psr\Http\Message\StreamInterface;

use function explode;
use function json_encode;

class TraceableMessageTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(MessageInterface::class, $this->message());
    }

    /**
     * @test
     */
    public function get_returns_value()
    {
        $message = $this->message([Message::POOL_CUSTOM => ['foo' => 'bar']]);
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
        $message = $this->message([Message::POOL_CUSTOM => ['foo' => 'bar']]);
        $this->assertEquals('bar', $message->getOrFail('foo'));
        $this->expectException(ItemNotFoundException::class);
        $message->getOrFail('foofoo');
    }

    /**
     * @test
     */
    public function property_access()
    {
        $data = [
            'foo' => 'bar'
        ];
        $json = json_encode($data);
        $message = $this->message([
            Message::POOL_CUSTOM => ['foo' => 'bar'],
            'envelope' => ['Content-Type' => 'application/json'],
            'payload'  => $json,
            'protocolVersion' => '1.1'
        ]);
        $this->assertEquals(AbstractMessage::TYPE_CUSTOM, $message->type);
        $this->assertEquals('bar', $message->custom['foo']);
        $this->assertEquals('application/json', $message->envelope['Content-Type']);
        $this->assertEquals('application/json', $message->headers['Content-Type']);
        $this->assertEquals($json, $message->payload);
        $this->assertEquals('1.1', $message->protocolVersion);
        $this->assertEquals(AbstractMessage::TRANSPORT_APP, $message->transport);

        $this->expectException(KeyNotFoundException::class);
        $this->assertTrue($message->foo);

    }

    /**
     * @test
     */
    public function set_sets_value()
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

        $message = $this->message([AbstractMessage::POOL_CUSTOM => $attributes]);

        foreach ($attributes as $key=>$value) {
            $this->assertTrue(isset($message[$key]));
            $this->assertEquals($value, $message[$key]);
        }
        $this->assertFalse(isset($message['baz']));

    }

    /**
     * @test
     */
    public function offsetSet_throws_exception()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message([AbstractMessage::POOL_CUSTOM => $attributes]);

        $this->expectException(UnsupportedUsageException::class);
        $message['foo'] = 'baz';

    }

    /**
     * @test
     */
    public function offsetUnset_throws_exception()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message([AbstractMessage::POOL_CUSTOM => $attributes]);

        $this->expectException(UnsupportedUsageException::class);
        unset($message['foo']);

    }

    /**
     * @test
     */
    public function toArray_returns_attributes()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message([AbstractMessage::POOL_CUSTOM => $attributes]);

        $this->assertEquals($attributes, $message->toArray());

    }

    /**
     * @test
     */
    public function with_returns_changed_instance()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message([AbstractMessage::POOL_CUSTOM => $attributes]);
        $fork = $message->with('foo', 'baz');
        $this->assertNotSame($message, $fork);

        $this->assertEquals('bar', $message['foo']);
        $this->assertEquals('baz', $fork['foo']);
    }

    /**
     * @test
     */
    public function without_returns_changed_instance()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message([AbstractMessage::POOL_CUSTOM => $attributes]);
        $fork = $message->without('foo');
        $this->assertNotSame($message, $fork);

        $this->assertEquals('bar', $message['foo']);
        $this->assertFalse(isset($fork['foo']));
    }

    /**
     * @test
     */
    public function getProtocolVersion_and_withProtocolVersion()
    {
        $message = $this->message(['protocolVersion' => '1.1']);
        $fork = $message->withProtocolVersion('2.0');
        $this->assertNotSame($message, $fork);

        $this->assertEquals('1.1', $message->getProtocolVersion());
        $this->assertEquals('2.0', $fork->getProtocolVersion());
    }

    /**
     * @test
     */
    public function getHeaders_returns_headers()
    {
        $header = [
            'Date' => 'Fri, 10 Nov 2017 20:30:00 GMT',
            'Server' => 'Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By' => 'PHP/5.6.1',
            'Content-Length' => '100',
            'Connection' => 'close',
            'Content-Type' =>  'application/json',
            'X-Multiple' => 'a,b,c'
        ];

        $message = $this->message(['envelope' => $header]);
        $headers = $message->getHeaders();

        foreach ($header as $key=>$value) {
            $this->assertEquals(explode(',', $value), $headers[$key]);
        }

        $this->assertTrue($message->hasHeader('Date'));
        $this->assertFalse($message->hasHeader('Foo'));
    }

    /**
     * @test
     */
    public function hasHeader_checks_if_has()
    {
        $header = [
            'Date' => 'Fri, 10 Nov 2017 20:30:00 GMT',
            'Server' => 'Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By' => 'PHP/5.6.1',
            'Content-Length' => '100',
            'Connection' => 'close',
            'Content-Type' =>  'application/json',
            'X-Multiple' => 'a,b,c'
        ];

        $message = $this->message(['envelope' => $header]);

        foreach ($header as $key=>$value) {
            $this->assertTrue($message->hasHeader($key));
        }

        $this->assertTrue($message->hasHeader('date')); // case insensitive
        $this->assertFalse($message->hasHeader('Foo'));
    }

    /**
     * @test
     */
    public function getHeader_returns_headers()
    {
        $header = [
            'Date' => 'Fri, 10 Nov 2017 20:30:00 GMT',
            'Server' => 'Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By' => 'PHP/5.6.1',
            'Content-Length' => '100',
            'Connection' => 'close',
            'Content-Type' =>  'application/json',
            'X-Multiple' => 'a,b,c'
        ];

        $message = $this->message(['envelope' => $header]);

        $this->assertEquals([$header['X-Powered-By']], $message->getHeader('x-powered-by'));
        $this->assertEquals(['a','b','c'], $message->getHeader('x-multiple'));

        $this->assertSame([], $message->getHeader('foo'));

    }

    /**
     * @test
     */
    public function getHeaderLine_returns_header_as_string()
    {
        $header = [
            'Date' => 'Fri, 10 Nov 2017 20:30:00 GMT',
            'Server' => 'Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By' => 'PHP/5.6.1',
            'Content-Length' => '100',
            'Connection' => 'close',
            'Content-Type' =>  'application/json',
            'X-Multiple' => 'a,b,c'
        ];

        $message = $this->message(['envelope' => $header]);

        $this->assertEquals($header['X-Powered-By'], $message->getHeaderLine('x-powered-by'));
        $this->assertEquals('a,b,c', $message->getHeaderLine('x-multiple'));

        $this->assertSame('', $message->getHeaderLine('foo'));

    }

    /**
     * @test
     */
    public function withHeader_returns_new_instance_with_changed_header()
    {
        $header = [
            'Date' => 'Fri, 10 Nov 2017 20:30:00 GMT',
            'Server' => 'Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By' => 'PHP/5.6.1',
            'Content-Length' => '100',
            'Connection' => 'close',
            'Content-Type' =>  'application/json',
            'X-Multiple' => 'a,b,c'
        ];

        $message = $this->message(['envelope' => $header]);

        $fork = $message->withHeader('Server', 'Nginx');

        $this->assertEquals($header['Server'], $message->envelope['Server']);
        $this->assertEquals('Nginx', $fork->envelope['Server']);

    }

    /**
     * @test
     */
    public function withAddedHeader_returns_new_instance_with_changed_header()
    {
        $header = [
            'Date' => 'Fri, 10 Nov 2017 20:30:00 GMT',
            'Server' => 'Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By' => 'PHP/5.6.1',
            'Content-Length' => '100',
            'Connection' => 'close',
            'Content-Type' =>  'application/json',
            'X-Multiple' => 'a,b,c'
        ];

        $message = $this->message(['envelope' => $header]);

        $fork = $message->withAddedHeader('X-Multiple', 'd');

        $this->assertEquals($header['X-Multiple'], $message->envelope['X-Multiple']);
        $this->assertEquals('a,b,c,d', $fork->envelope['X-Multiple']);

    }

    /**
     * @test
     */
    public function withoutHeader_returns_new_instance_without_header()
    {
        $header = [
            'Date' => 'Fri, 10 Nov 2017 20:30:00 GMT',
            'Server' => 'Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By' => 'PHP/5.6.1',
            'Content-Length' => '100',
            'Connection' => 'close',
            'Content-Type' =>  'application/json',
            'X-Multiple' => 'a,b,c'
        ];

        $message = $this->message(['envelope' => $header]);

        $fork = $message->withoutHeader('Date');

        $this->assertEquals($header['Date'], $message->envelope['Date']);
        $this->assertFalse($fork->hasHeader('Date'));

    }

    /**
     * @test
     */
    public function getBody_returns_stream_with_payload()
    {
        $message = $this->message(['payload' => 'Blabla']);

        $body = $message->getBody();
        $this->assertInstanceOf(StreamInterface::class, $body);
        $this->assertEquals('Blabla', (string)$body);

        $content = new StringStream('This is a string');
        $message = $this->message(['payload' => $content]);
        $this->assertSame($content, $message->getBody());

        $urlString = 'https://github.com';
        $url = new Url($urlString);
        $message = $this->message(['payload' => $url]);
        $body = $message->getBody();
        $this->assertInstanceOf(StreamInterface::class, $body);
        $this->assertEquals($urlString, (string)$body);
        $this->assertSame($url, $body->getString());

    }

    /**
     * @test
     */
    public function withBody_returns_new_instance()
    {
        $string1 = 'Blabla';
        $string2 = 'Blibli';
        $stream2 = new StringStream($string2);

        $message = $this->message(['payload' => $string1]);
        $fork = $message->withBody($stream2);

        $this->assertNotSame($fork, $message);

        $this->assertInstanceOf(StreamInterface::class, $message->getBody());
        $this->assertInstanceOf(StreamInterface::class, $fork->getBody());

        $this->assertEquals($string1, (string)$message->getBody());
        $this->assertEquals($string2, (string)$fork->getBody());

        $this->assertEquals($string1, (string)$message->body);
        $this->assertEquals($string2, (string)$fork->body);

        $this->assertSame($message->next, $fork);
        $this->assertSame($fork->previous, $message);
        $this->assertNull($fork->next);
        $this->assertNull($message->previous);
    }

    /**
     * @param array $attributes
     * @return TraceableMessage
     */
    protected function message(array $attributes=[]) : TraceableMessage
    {
        return new TraceableMessage($attributes);
    }
}
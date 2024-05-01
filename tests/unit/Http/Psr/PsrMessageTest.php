<?php
/**
 *  * Created by mtils on 12.12.2021 at 18:17.
 **/

namespace Ems\Http\Psr;

use ArrayAccess;
use Countable;
use Ems\Contracts\Core\Message;
use Ems\Core\ImmutableMessage;
use Ems\Core\Collections\ItemNotFoundException;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Ems\Core\Filesystem\StringStream;
use Ems\Core\Url;
use Ems\Http\Psr\PsrMessageTrait;
use Ems\TestCase;
use IteratorAggregate;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

use function explode;
use function iterator_to_array;
use function json_encode;

class PsrMessageTest extends TestCase
{
    #[Test] public function it_implements_interface()
    {
        $message = $this->message();
        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertInstanceOf(ArrayAccess::class, $message);
        $this->assertInstanceOf(IteratorAggregate::class, $message);
        $this->assertInstanceOf(Countable::class, $message);
    }

    #[Test] public function behaves_like_array()
    {
        $attributes = [
            'foo' => 'bar',
            'test' => ['a','b'],
            'activated' => true
        ];

        $message = $this->message([Message::POOL_CUSTOM => $attributes]);

        $this->assertCount(count($attributes), $message);
        $this->assertEquals($attributes, iterator_to_array($message));

        $message = $this->message([Message::POOL_CUSTOM => $attributes]);

        $test = [];
        foreach ($message as $key=>$value) {
            $test[$key] = $value;
        }
        $this->assertEquals($attributes, $test);

    }

    #[Test] public function getProtocolVersion_and_withProtocolVersion()
    {
        $message = $this->message(['protocolVersion' => '1.1']);
        $fork = $message->withProtocolVersion('2.0');
        $this->assertNotSame($message, $fork);

        $this->assertEquals('1.1', $message->getProtocolVersion());
        $this->assertEquals('2.0', $fork->getProtocolVersion());
    }

    #[Test] public function getHeaders_returns_headers()
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

    #[Test] public function hasHeader_checks_if_has()
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

    #[Test] public function getHeader_returns_headers()
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

    #[Test] public function getHeaderLine_returns_header_as_string()
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

    #[Test] public function withHeader_returns_new_instance_with_changed_header()
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

    #[Test] public function withAddedHeader_returns_new_instance_with_changed_header()
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

    #[Test] public function withoutHeader_returns_new_instance_without_header()
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

    #[Test] public function getBody_returns_stream_with_payload()
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

    #[Test] public function withBody_returns_new_instance()
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
     * @return ImmutablePsrMessage
     */
    protected function message(array $attributes=[]) : ImmutablePsrMessage
    {
        return new ImmutablePsrMessage($attributes);
    }
}

class ImmutablePsrMessage extends ImmutableMessage implements MessageInterface
{
    use PsrMessageTrait;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (isset($attributes['protocolVersion'])) {
            $this->protocolVersion = $attributes['protocolVersion'];
        }
    }

    public function __get(string $key)
    {
        if ($key == 'body') {
            return $this->getBody();
        }
        return parent::__get($key);
    }


}
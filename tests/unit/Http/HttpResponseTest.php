<?php
/**
 *  * Created by mtils on 26.12.2021 at 16:01.
 **/

namespace Ems\Http;

use DateTime;
use Ems\Contracts\Core\Message;
use Ems\Contracts\Core\Stream;
use Ems\Contracts\Http\Cookie;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Expression;
use Ems\Core\KeyExpression;
use Ems\Core\Response as CoreResponse;
use Ems\Core\Serializer\JsonSerializer;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class HttpResponseTest extends TestCase
{
    #[Test] public function it_implements_interface()
    {
        $response = $this->response();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(CoreResponse::class, $response);
    }


    #[Test] public function it_applies_all_attributes()
    {
        $attributes = [
            'type'      => Message::TYPE_OUTPUT,
            'transport' => Message::TRANSPORT_NETWORK,
            'custom'    => ['foo' => 'bar'],
            'headers'   => ['Content-Type' =>  'application/json'],
            'payload'   => 'raw_body_content',
            'protocolVersion' => '2.0',
            'raw'       => 'raw_body_content',
        ];
        $response = $this->response($attributes);

        foreach ($attributes as $key=>$value) {
            $this->assertEquals($value, $response->$key);
        }
    }

    #[Test] public function it_applies_separate_constructor_attributes()
    {
        $data = ['foo' => 'bar'];
        $headers = ['Content-Type' =>  'application/json'];
        $status = 201;
        $response = $this->response($data, $headers, $status);
        $this->assertEquals($data, $response->payload);
        $this->assertEquals($data, $response->custom);
        $this->assertEquals($headers, $response->headers);
        $this->assertEquals($status, $response->status);
    }

    #[Test] public function it_changes_status()
    {
        $response = $this->response();
        $this->assertEquals(200, $response->status);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('', $response->getReasonPhrase());
        $this->assertSame('', $response->statusMessage);

        $fork = $response->withStatus(404);
        $this->assertNotSame($response, $fork);
        $this->assertEquals(404, $fork->getStatusCode());

        $fork2 = $fork->withStatus(401, 'Access denied');
        $this->assertNotSame($fork, $fork2);
        $this->assertEquals(401, $fork2->getStatusCode());
        $this->assertEquals('Access denied', $fork2->getReasonPhrase());

    }

    #[Test] public function it_creates_body()
    {
        $response = $this->response('blob');

        $this->assertEquals('blob', $response->payload);
        $body = $response->body;
        $this->assertInstanceOf(Stream::class, $body);
        $this->assertInstanceOf(StreamInterface::class, $body);
        $this->assertEquals('blob', "$body");
    }

    #[Test] public function status_from_header_if_none_set()
    {
        $headers = [
            'HTTP/1.1 207 OK',
            'Date: Fri, 10 Nov 2017 20:30:00 GMT',
            'Server: Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By: PHP/5.6.1',
            'Content-Length: 100',
            'Connection: close',
            'Content-Type: application/json'
        ];

        $this->assertEquals(207, $this->response('', $headers)->status);

    }

    #[Test] public function contentType_from_header_if_none_set()
    {
        $headers = [
            'HTTP/1.1 207 OK',
            'Date: Fri, 10 Nov 2017 20:30:00 GMT',
            'Server: Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By: PHP/5.6.1',
            'Content-Length: 100',
            'Connection: close',
            'Content-Type: application/json'
        ];

        $this->assertEquals('application/json', $this->response('', $headers)->contentType);

    }

    #[Test] public function protocolVersion_from_header_if_none_set()
    {
        $headers = [
            'HTTP/1.2 207 OK',
            'Date: Fri, 10 Nov 2017 20:30:00 GMT',
            'Server: Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By: PHP/5.6.1',
            'Content-Length: 100',
            'Connection: close',
            'Content-Type: application/json'
        ];

        $this->assertEquals('1.2', $this->response('', $headers)->protocolVersion);

    }

    #[Test] public function contentType_from_header_if_not_readable()
    {
        $headers = [
            'HTTP/1.1 207 OK',
            'Date: Fri, 10 Nov 2017 20:30:00 GMT',
            'Server: Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By: PHP/5.6.1',
            'Content-Length: 100',
            'Connection: close',
            'Content-Type: '
        ];

        $response = $this->response('', $headers);
        $this->assertEquals('', $response->contentType);

    }

    #[Test] public function body_gets_rendered_from_scalar_payload()
    {
        $response = $this->response();
        $fork = $response->withPayload(null);
        $this->assertNotSame($fork, $response);
        $this->assertEquals('', (string)$fork->body);
        $this->assertEquals('', (string)$fork);

        $response = $this->response(15.4);
        $this->assertEquals('15.4', (string)$response->body);
    }

    #[Test] public function body_gets_rendered_from_stringable()
    {
        $body = new Expression('foo');
        $response = $this->response($body);
        $this->assertEquals('foo', (string)$response->body);
    }

    #[Test] public function body_gets_rendered_from_toString()
    {
        $payload = new HttpResponseTest_String();
        $response = $this->response($payload);

        $this->assertEquals('hi', (string)$response->body);
        $this->assertEquals('hi', "$response");
    }

    #[Test] public function body_gets_rendered_threw_serializer()
    {
        $response = $this->response();
        $serializer = $this->serializer();
        $response->provideSerializerBy(function () use ($serializer) {
            return $serializer;
        });

        $payload = ['a', 'b', 'c'];
        $serialized = $serializer->serialize($payload);

        $fork = $response->withPayload($payload);

        $this->assertEquals($serialized, (string)$fork->body);
        $this->assertEquals($serialized, (string)$fork);
    }

    #[Test] public function body_throws_exception_if_needs_serializer_and_none_assigned()
    {
        $response = $this->response(['foo' => 'bar']);
        $this->expectException(UnConfiguredException::class);
        $response->getBody();
    }

    #[Test] public function test_raw_data()
    {
        $response = $this->response(['raw' => 'blob']);
        $fork = $response->with('foo', 'bar');
        $this->assertNotSame($response, $fork);
        $this->assertEquals('blob', $response->raw);
        $this->assertEquals('blob', $fork->raw);
    }

    #[Test] public function payload_getter_and_setter()
    {
        $response = $this->response(['haha']);
        $this->assertEquals(['haha'], $response->payload);
    }

    #[Test] public function test_setPayload_with_stringable()
    {
        $expression = new KeyExpression('haha');
        $response = $this->response($expression);
        $this->assertSame($expression, $response->payload);
        $this->assertEquals('haha', (string)$response->body);
        $this->assertEquals('haha', (string)$response);
    }

    #[Test] public function test_payload_returns_null_if_no_body_found()
    {
        $response = $this->response();
        $this->assertNull($response->payload);
    }

    #[Test] public function toArray_throws_exception_if_no_serializer_assigned()
    {
        $response = $this->response(123456);
        $this->expectException(UnConfiguredException::class);
        $response->toArray();
    }

    #[Test] public function custom_deserializes_body()
    {

        $serializer = $this->serializer();

        $data = [
            'a' => 'b',
            'b' => 'c'
        ];

        $json = $serializer->serialize($data);
        $response = $this->response(['payload' => $json]);
        $response->provideSerializerBy(function () use ($serializer) {
            return $serializer;
        });

        $this->assertEquals($data, $response->custom);

    }

    #[Test] public function toArray_deserializes()
    {

        $serializer = $this->serializer();

        $data = [
            'a' => 'b',
            'b' => 'c'
        ];

        $json = $serializer->serialize($data);

        $response = $this->response($json);
        $response->provideSerializerBy(function () use ($serializer) {
            return $serializer;
        });
        $this->assertEquals($data, $response->toArray());

    }

    #[Test] public function iterate()
    {

        $serializer = $this->serializer();

        $data = [
            'a' => 'b',
            'b' => 'c'
        ];

        $json = $serializer->serialize($data);

        $response = $this->response($json);
        $response->provideSerializerBy(function () use ($serializer) {
            return $serializer;
        });

        $result = [];

        foreach ($response as $key=>$value) {
            $result[$key] = $value;
        }
        $this->assertEquals($data, $result);

    }

    #[Test] public function count_triggers_deserialize()
    {
        $serializer = $this->serializer();

        $data = [
            'a' => 'b',
            'b' => 'c'
        ];

        $json = $serializer->serialize($data);

        $response = $this->response($json);
        $response->provideSerializerBy(function () use ($serializer) {
            return $serializer;
        });

        $this->assertCount(count($data), $response);

    }

    #[Test] public function arrayAccess_triggers_deserialization()
    {
        $serializer = $this->serializer();

        $data = [
            'a' => 'b',
            'b' => 'c'
        ];

        $json = $serializer->serialize($data);

        $response = $this->response($json);
        $response->provideSerializerBy(function () use ($serializer) {
            return $serializer;
        });

        $this->assertEquals($data['a'], $response['a']);

    }

    #[Test] public function withCookie_with_separate_parameters()
    {
        $response = $this->response();
        $this->assertSame([], $response->cookies);

        $cookieResponse = $response->withCookie('foo', 'bar');
        $this->assertNotSame($cookieResponse, $response);
        $this->assertInstanceOf(Cookie::class, $cookieResponse->cookies['foo']);
        $this->assertEquals('bar', $cookieResponse->cookies['foo']->value);

        $expire = new DateTime('2022-01-15 08:36:00');
        $cookieResponse2 = $cookieResponse->withCookie('a', 'b', $expire, '/users', 'localhost', false, false, Cookie::STRICT);

        $this->assertNotSame($cookieResponse2, $cookieResponse);
        $this->assertInstanceOf(Cookie::class, $cookieResponse2->cookies['foo']);
        $this->assertEquals('bar', $cookieResponse2->cookies['foo']->value);

        $cookie = $cookieResponse2->cookies['a'];
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEquals('b', $cookie->value);
        $this->assertSame($expire, $cookie->expire);
        $this->assertEquals('/users', $cookie->path);
        $this->assertEquals('localhost', $cookie->domain);
        $this->assertFalse($cookie->secure);
        $this->assertFalse($cookie->httpOnly);
        $this->assertEquals(Cookie::STRICT, $cookie->sameSite);

    }

    #[Test] public function withSecureCookies()
    {
        $response = $this->response();
        $this->assertSame([], $response->cookies);
        $this->assertTrue($response->secureCookies);
        $cookieResponse = $response->withSecureCookies(false)->withCookie('foo', 'bar');
        $this->assertFalse($cookieResponse->secureCookies);

        $this->assertNotSame($cookieResponse, $response);
        $this->assertInstanceOf(Cookie::class, $cookieResponse->cookies['foo']);
        $this->assertFalse($cookieResponse->cookies['foo']->secure);

    }

    #[Test] public function withCookie_with_Cookie_object()
    {
        $expire = new DateTime('2022-01-15 08:36:00');
        $cookie = new Cookie('a', 'b', $expire, '/users', 'localhost', false, false, Cookie::STRICT);
        $response = $this->response();
        $cookieResponse = $response->withCookie($cookie);
        $this->assertNotSame($cookieResponse, $response);
        $this->assertSame($cookie, $cookieResponse->cookies['a']);
    }

    #[Test] public function withoutCookie_deletes_cookie()
    {
        $response = $this->response();
        $this->assertSame([], $response->cookies);

        $cookieResponse = $response->withCookie('foo', 'bar');
        $this->assertInstanceOf(Cookie::class, $cookieResponse->cookies['foo']);

        $cookieResponse2 = $cookieResponse->withoutCookie('foo');
        $this->assertFalse(isset($cookieResponse2->cookies['foo']));
        $this->assertTrue(isset($cookieResponse->cookies['foo']));
        $this->assertSame($cookieResponse, $cookieResponse2->previous);
        $this->assertSame($cookieResponse->next, $cookieResponse2);
    }

    protected function response(...$args) : HttpResponse
    {
        return new HttpResponse(...$args);
    }

    protected function serializer()
    {
        return (new JsonSerializer())->asArrayByDefault();
    }
}

class HttpResponseTest_String
{
    public function __toString()
    {
        return 'hi';
    }
}
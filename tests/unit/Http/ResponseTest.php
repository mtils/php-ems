<?php

/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 20:08
 */

namespace Ems\Http;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\Serializer;
use Ems\Contracts\Http\Connection;
use Ems\Contracts\Http\Response as ResponseContract;
use Ems\Core\Expression;
use Ems\Core\KeyExpression;
use Ems\Core\Serializer\JsonSerializer;
use Ems\Core\Url;

class ResponseTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(ResponseContract::class, $this->response());
    }

    public function test_status_getter_and_setter()
    {
        $response = $this->response();
        $this->assertSame($response, $response->setStatus(201));
        $this->assertEquals(201, $response->status());
    }

    public function test_status_from_header_if_none_set()
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

        $this->assertEquals(207, $this->response($headers)->status());

    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function test_if_no_status_found_exception_will_throw()
    {
        $headers = [
            'Date: Fri, 10 Nov 2017 20:30:00 GMT',
            'Server: Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By: PHP/5.6.1',
            'Content-Length: 100',
            'Connection: close',
            'Content-Type: application/json'
        ];

        $this->response($headers)->status();

    }

    public function test_contentType_getter_and_setter()
    {
        $response = $this->response();
        $this->assertSame($response, $response->setContentType('text/html'));
        $this->assertEquals('text/html', $response->contentType());
    }

    public function test_contentType_from_header_if_none_set()
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

        $this->assertEquals('application/json', $this->response($headers)->contentType());

    }

    public function test_contentType_from_header_if_not_readable()
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

        $this->assertEquals('', $this->response($headers)->contentType());

    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function test_content_throws_notImplemented()
    {
        $this->response()->content();
    }

    public function test_body_getter_and_setter()
    {
        $response = $this->response();
        $this->assertSame($response, $response->setBody('foo'));
        $this->assertEquals('foo', $response->body());
    }

    public function test_construct_with_string()
    {
        $response = $this->response([], 'foo');
        $this->assertEquals('foo', $response->body());
    }

    public function test_body_gets_rendered_from_scalar_payload()
    {
        $response = $this->response();
        $this->assertSame($response, $response->setPayload(null));
        $this->assertEquals('', $response->body());

        $response = $this->response();
        $response->setPayload(15.4);
        $this->assertEquals('15.4', $response->body());
    }

    public function test_body_gets_rendered_from_stringable()
    {
        $body = new Expression('foo');
        $response = $this->response([], $body);
        $this->assertEquals('foo', $response->body());
    }

    public function test_body_gets_rendered_from_toString()
    {
        $response = $this->response();
        $payload = new ResponseTest_String();
        $this->assertSame($response, $response->setPayload($payload));
        $this->assertEquals('hi', $response->body());
        $this->assertEquals('hi', "$response");
    }

    public function test_body_gets_rendered_threw_serializer()
    {
        $response = $this->response();
        $serializer = $this->serializer();
        $response->setSerializer($serializer);

        $payload = ['a', 'b', 'c'];
        $serialized = $serializer->serialize($payload);

        $this->assertSame($response, $response->setPayload($payload));
        $this->assertEquals($serialized, $response->body());
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\ConfigurationError
     */
    public function test_body_throws_exception_if_needs_serializer_and_none_assigned()
    {
        $response = $this->response();
        $this->assertSame($response, $response->setPayload([]));
        $response->body();
    }

    public function test_raw_getter_and_setter()
    {
        $response = $this->response();
        $this->assertSame($response, $response->setRaw('foo'));
        $this->assertEquals('foo', $response->raw());
    }

    public function test_payload_getter_and_setter()
    {
        $response = $this->response();
        $this->assertSame($response, $response->setPayload(['haha']));
        $this->assertEquals(['haha'], $response->payload());
    }

    public function test_setPayload_with_stringable()
    {
        $response = $this->response();
        $this->assertSame($response, $response->setPayload(new KeyExpression('haha')));
        $this->assertEquals('haha', $response->payload());
        $this->assertEquals('haha', $response->body());
    }

    public function test_payload_returns_null_if_no_body_found()
    {
        $response = $this->response();
        $this->assertNull($response->payload());
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\ConfigurationError
     */
    public function test_payload_throws_exception_if_no_serializer_assigned()
    {
        $response = $this->response();
        $response->setBody(123456);
        $response->payload();
    }

    public function test_payload_deserializes_body()
    {
        $response = $this->response();
        $serializer = $this->serializer();

        $data = (object)[
            'a' => 'b',
            'b' => 'c'
        ];

        $json = $serializer->serialize($data);
        $response->setBody($json)->setSerializer($serializer);
        $this->assertEquals($serializer, $response->getSerializer());
        $this->assertEquals($data, $response->payload());

    }

    public function test_toArray()
    {
        $response = $this->response();
        $serializer = $this->serializer();

        $data = [
            'a' => 'b',
            'b' => 'c'
        ];

        $obj = (object)$data;

        $json = $serializer->serialize($data);
        $response->setBody($json)->setSerializer($serializer);
        $this->assertEquals($serializer, $response->getSerializer());

        $this->assertEquals($data, $response->toArray());

    }

    public function test_iterate()
    {
        $response = $this->response();
        $serializer = $this->serializer();

        $data = [
            'a' => 'b',
            'b' => 'c'
        ];

        $obj = (object)$data;

        $json = $serializer->serialize($data);
        $response->setBody($json)->setSerializer($serializer);
        $this->assertEquals($serializer, $response->getSerializer());

        $result = [];

        foreach ($response as $key=>$value) {
            $result[$key] = $value;
        }
        $this->assertEquals($data, $result);

    }

    public function test_count()
    {
        $response = $this->response();
        $serializer = $this->serializer();

        $data = [
            'a' => 'b',
            'b' => 'c'
        ];

        $json = $serializer->serialize($data);
        $response->setBody($json)->setSerializer($serializer);
        $this->assertEquals($serializer, $response->getSerializer());

        $this->assertCount(2, $response);

    }

    public function test_count_from_arrayable()
    {
        $response = $this->response();
        $serializer = $this->serializer();

        $data = [
            'a' => 'b',
            'b' => 'c'
        ];

        $json = $serializer->serialize($data);
        $response->setSerializer($serializer);
        $this->assertEquals($serializer, $response->getSerializer());

        $response2 = $this->response()->setPayload([1,2,3]);
        $response->setPayload($response2);
        $this->assertCount(3, $response);

    }

    public function test_count_from_iterator()
    {
        $response = $this->response();
        $serializer = $this->serializer();

        $data = [
            'a' => 'b',
            'b' => 'c'
        ];

        $array = new \ArrayObject($data);
        $response->setPayload($array);
        $this->assertCount(2, $response);

    }

    public function test_count_from_not_arraylike_iterator()
    {
        $response = $this->response();
        $serializer = $this->serializer();

        $response->setSerializer($serializer)->setPayload( new JsonSerializer);
        $this->assertCount(0, $response);

    }

    protected function response($headers=[], $body='')
    {
        return new Response($headers, $body);
    }

    protected function serializer()
    {
        return new JsonSerializer();
    }
}

class ResponseTest_String
{
    public function __toString()
    {
        return 'hi';
    }
}
<?php

/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 20:08
 */

namespace Ems\Http;

use Ems\Contracts\Core\ConnectionPool;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Http\Connection as HttpConnection;
use Ems\Contracts\Http\Response as ResponseContract;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Url;
use Ems\Contracts\Http\Client as ClientContract;
use Ems\Core\Expression;
use Ems\Http\Serializer\UrlEncodeSerializer;
use function json_encode;

class ClientTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(ClientContract::class, $this->newClient());
    }

    public function test_head_forwards_to_connection()
    {
        $con = $this->con();
        $pool = $this->pool();
        $client = $this->newClient($pool);

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);

        $pool->shouldReceive('connection')
            ->with($url)
            ->once()
            ->andReturn($con);

        $con->shouldReceive('send')
            ->with('HEAD')
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->head($url));
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function test_any_call_to_con_throws_exception_if_no_http_connection_returned()
    {
        $pool = $this->pool();
        $client = $this->newClient($pool);

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');

        $pool->shouldReceive('connection')
            ->with($url)
            ->once()
            ->andReturn('foo');

        $client->head($url);
    }

    public function test_get_forwards_to_connection()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);

        $con->shouldReceive('send')
            ->with('GET', [])
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->get($url));
    }

    public function test_get_forwards_to_connection_with_headers()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);

        $con->shouldReceive('send')
            ->with('GET', ['Accept: application/json'])
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->get($url, 'application/json'));
    }

    public function test_post_forwards_to_connection()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);

        $con->shouldReceive('send')
            ->with('POST', [], 'content')
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->post($url, 'content'));
    }

    public function test_post_forwards_to_connection_with_stringable_data()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);

        $con->shouldReceive('send')
            ->with('POST', [], 'content')
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->post($url, new Expression('content')));
    }

    public function test_post_forwards_to_connection_with_array_data()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);
        $data = ['a', 'b'];
        $serialized = json_encode($data);

        $con->shouldReceive('send')
            ->with('POST', ['Accept: application/json'], $serialized)
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->post($url, $data, 'application/json'));
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\ConfigurationError
     */
    public function test_post_throws_exception_if_data_not_serializable()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $data = ['a', 'b'];
        $client->post($url, $data, 'application/json-api');

    }

    public function test_put_forwards_to_connection()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);

        $con->shouldReceive('send')
            ->with('PUT', [], 'content')
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->put($url, 'content'));
    }

    public function test_patch_forwards_to_connection()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);

        $con->shouldReceive('send')
            ->with('PATCH', [], 'content')
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->patch($url, 'content'));
    }

    public function test_delete_forwards_to_connection()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);

        $con->shouldReceive('send')
            ->with('DELETE', [], 'content')
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->delete($url, 'content'));
    }

    public function test_header_adds_headers_when_sending()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);

        $additionalHeader = 'Accept-Encoding: gzip,deflate';
        $con->shouldReceive('send')
            ->with('GET', [$additionalHeader,'Accept: application/json'])
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->header([$additionalHeader])->get($url, 'application/json'));
    }

    public function test_header_adds_single_header_when_sending()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);

        $additionalHeader = 'Accept-Encoding: gzip,deflate';
        $con->shouldReceive('send')
            ->with('GET', [$additionalHeader,'Accept: application/json'])
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->header('Accept-Encoding', 'gzip,deflate')->get($url, 'application/json'));
    }

    public function test_submit_submits_data()
    {
        $con = $this->con();
        $client = $this->newClient($this->pool($con));
        $serializer = new UrlEncodeSerializer();

        $url = new Url('http://web-utils.de/api/v1/slots/email.sent');
        $response = new Response([0=>'HTTP/1.1 200 OK']);
        $data = ['a', 'b'];
        $serialized = $serializer->serialize($data);

        $con->shouldReceive('send')
            ->with('POST', ['Accept: application/json', 'Content-Type: ' . $serializer->mimeType()], $serialized)
            ->once()
            ->andReturn($response);

        $this->assertSame($response, $client->submit($url, $data));
    }

    protected function newClient(ConnectionPool $pool=null)
    {
        return new Client($pool ?: $this->pool());
    }

    protected function pool($con = null)
    {
        $pool = $this->mock(ConnectionPool::class);

        if ($con) {
            $pool->shouldReceive('connection')
                ->andReturn($con);
        }

        return $pool;
    }

    protected function con()
    {
        return $this->mock(HttpConnection::class);
    }

}
<?php
/**
 *  * Created by mtils on 21.10.18 at 13:52.
 **/

namespace Ems\Http;


use Ems\Contracts\Http\Connection;
use Ems\Core\Helper;
use Ems\Core\Url;
use Ems\HttpMockTest;
use Ems\IntegrationTest;
use Ems\Testing\LoggingCallable;
use PHPUnit\Framework\Attributes\Test;
use function file_get_contents;
use InterNations\Component\HttpMock\PHPUnit\HttpMockTrait;
use function var_dump;

class FilesystemConnectionIntegrationTest extends HttpMockTest
{
    #[Test] public function read_from_url()
    {

        $path = '/foo';
        $method = 'GET';
        $body = 'Some body';

        $this->http->mock
            ->when()
                ->methodIs($method)
                ->pathIs($path)
            ->then()
                //->header('Content-Type', 'text/plain')
                ->body($body)
            ->end();

        $this->http->setUp();

        $url = $this->url($path);

        $con = $this->con($url);

        $response = $con->send($method);

        $this->assertEquals($body, $response->body);
        $this->assertNotEmpty($response->raw);
        $this->assertNotEmpty($response->headers['Host']);

    }

    #[Test] public function read_from_url_with_basic_auth()
    {

        $path = '/foo';
        $method = 'GET';
        $body = 'Some other body';
        $user = 'michael';
        $password = '123';

        $this->http->mock
            ->when()
            ->methodIs($method)
            ->pathIs($path)
            ->then()
            ->header('Content-Type', 'text/plain')
            ->body($body)
            ->end();

        $this->http->setUp();

        $url = $this->url($path)->user($user)->password($password);

        $con = $this->con($url);

        $listener = new LoggingCallable();

        $con->onBefore('send', $listener);

        $con->send($method);

        $sentHeaders = $listener->arg(2);

        $hit = false;

        foreach ($sentHeaders as $headerLine) {
            if (Helper::startsWith($headerLine, 'Authorization: ')) {
                $hit = true;
            }
        }

        $this->assertTrue($hit);

    }

    #[Test] public function post_to_url()
    {

        $path = '/foo';
        $method = 'POST';
        $body =
            '{
  "code" : "201",
  "message" : "Resource created"
}
';

        $sendBody =
            '{
  "a" : "b",
  "c" : "d",
  "e" : "f",
  "g" : ["a", "b", "c", "d"],
  "h" : true,
  "i" : 13.4
}
';
        $this->http->mock
            ->when()
            ->methodIs($method)
            ->pathIs($path)
            ->then()
            ->header('Content-Type', 'application/json')
            ->body($body)
            ->end();

        $this->http->setUp();

        $url = $this->url($path);

        $con = $this->con($url);

        $response = $con->send($method, [], $sendBody);

        $request = $this->http->requests->last();

        $this->assertEquals($body, $response->payload);
        $this->assertEquals($sendBody, $request->getBody());
        $this->assertEquals('application/json', $response->headers['Content-Type']);

    }

    #[Test] public function methodHooks_contain_send()
    {
        $this->assertContains('send', $this->con('/')->methodHooks());
    }

    #[Test] public function read_method_returns_complete_message()
    {
        $path = '/foo';
        $method = 'GET';
        $body = 'Some body';

        $this->http->mock
            ->when()
            ->methodIs($method)
            ->pathIs($path)
            ->then()
            //->header('Content-Type', 'text/plain')
            ->body($body)
            ->end();

        $this->http->setUp();

        $url = $this->url($path);

        $con = $this->con($url);

        $result = $con->read();

        $this->assertStringStartsWith('HTTP/1', $result);
        $this->assertStringEndsWith('Some body', $result);

    }

    protected function con($url)
    {
        return new FilesystemConnection($url);
    }

    protected function url($path)
    {
        return (new Url())->scheme('http')
            ->host(static::$host)
            ->port(static::$port)
            ->path($path);
    }

    protected function postMessage()
    {
        $headers = [
            'HTTP/1.1 201 OK',
            'Date: Fri, 10 Nov 2017 20:30:00 GMT',
            'Server: Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By: PHP/5.6.1',
            'Content-Length: 100',
            'Connection: close',
            'Content-Type: application/json'
        ];

        $body =
            '{
  "code" : "201",
  "message" : "Resource created"
}
';

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }
}
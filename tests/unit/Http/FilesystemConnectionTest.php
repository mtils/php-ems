<?php

/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 20:08
 */

namespace Ems\Http;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Http\Connection;
use Ems\Contracts\Http\Response as ResponseContract;
use Ems\Core\Url;

class FilesystemConnectionTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(Connection::class, $this->con('http://bla.de'));
    }

    public function test_simple_get_request()
    {

        $url = 'http://tils.org/test.php';
        $fs = $this->mock(Filesystem::class);
        $con = $this->con($url, $fs);

        $fs->shouldReceive('handle')
            ->andReturn('handle');

        $fs->shouldReceive('read')
            ->with($url, 0, 'handle')
            ->once()
            ->andReturn($this->getMessage());

        $response = $con->send('GET', []);

        $this->assertInstanceOf(ResponseContract::class, $response);
        $this->assertEquals(200, $response->status());

    }

    public function test_simple_post_request()
    {
        $url = 'http://tils.org/test.php';
        $fs = $this->mock(Filesystem::class);
        $con = $this->con($url, $fs);

        $fs->shouldReceive('handle')
            ->andReturn('handle');

        $fs->shouldReceive('read')
            ->with($url, 0, 'handle')
            ->once()
            ->andReturn($this->postMessage());

        $response = $con->send('POST', []);
        $this->assertInstanceOf(ResponseContract::class, $response);
        $this->assertEquals(201, $response->status());

    }

    protected function con($url, Filesystem $fs=null)
    {
        $url = $url instanceof Url ? $url : new Url($url);
        return new FilesystemConnection($url, $fs);
    }

    protected function getMessage()
    {
        $headers = [
            'HTTP/1.1 200 OK',
            'Date: Fri, 10 Nov 2017 20:30:00 GMT',
            'Server: Apache/2.4.10 (Linux/SUSE)',
            'X-Powered-By: PHP/5.6.1',
            'Content-Length: 100',
            'Connection: close',
            'Content-Type: application/json'
        ];

        $body =

'{
  "a" : "b",
  "c" : "d",
  "e" : "f",
  "g" : ["a", "b", "c", "d"],
  "h" : true,
  "i" : 13.4
}
';

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
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
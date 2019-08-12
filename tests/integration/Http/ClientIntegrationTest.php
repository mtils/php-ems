<?php

use Ems\Core\ConnectionPool;
use Ems\Core\Url;
use Ems\Http\Client;
use Ems\Http\FilesystemConnection;
use Ems\IntegrationTest;
use InterNations\Component\HttpMock\PHPUnit\HttpMockTrait;

/**
 *  * Created by mtils on 12.08.19 at 15:32.
 **/

class ClientIntegrationTest extends IntegrationTest
{
    use HttpMockTrait;

    /**
     * @var string
     */
    protected static $host = 'localhost';

    /**
     * @var int
     */
    protected static $port = 8082;

    /**
     * @beforeClass
     */
    public static function bootHttpMock()
    {
        static::setUpHttpMockBeforeClass(static::$port, static::$host);
    }

    /**
     * @afterClass
     */
    public static function shutdownHttpMock()
    {
        static::tearDownHttpMockAfterClass();
    }

    /**
     * @before
     */
    public function setUpHttp()
    {
        $this->setUpHttpMock();
    }

    /**
     * @after
     */
    public function tearDownHttp()
    {
        $this->tearDownHttpMock();
    }

    /**
     * @test
     */
    public function submit_form_data()
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

        $client = $this->client();

        $data = [
            'foo' => 'bar',
            'girl' => 'power',
            'my' => [
                'multidimensional' => 'array'
            ]
        ];

        $response = $client->submit($url, $data);

        $request = $this->http->requests->last();

        $this->assertEquals($data, $request->getPostFields()->toArray());
        $this->assertEquals($body, $response->body());


    }

    protected function client()
    {
        $pool = new ConnectionPool();
        $pool->extend($pool->defaultConnectionName(), function ($url) {
            return $this->con($url);
        });
        return new Client($pool);
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
}
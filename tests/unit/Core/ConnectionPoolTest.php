<?php


namespace Ems\Core;

use Ems\Contracts\Core\ConnectionPool as ConnectionPoolContract;
use Ems\Contracts\Core\Errors\NotFound;

class ConnectionPoolTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceof(
            ConnectionPoolContract::class,
            $this->newPool()
        );
    }

    public function test_defaultConnectionName_get_and_set()
    {
        $pool = $this->newPool();

        $this->assertEquals('default', $pool->defaultConnectionName());
        $this->assertSame($pool, $pool->setDefaultConnectionName('foo'));
        $this->assertEquals('foo', $pool->defaultConnectionName());
    }

    public function test_connection_throws_exception_if_no_handler_found()
    {
        $this->expectException(NotFound::class);
        $pool = $this->newPool();

        $connection = $pool->connection();
    }

    public function test_connection_returns_result_of_extension()
    {

        $pool = $this->newPool();

        $pool->extend('php', function ($nameOrUrl) {
            return new FilesystemConnection($nameOrUrl);
        });

        $url = 'php://memory/';
        $connection = $pool->connection(new Url($url));

        $this->assertInstanceof(FilesystemConnection::class, $connection);
        $this->assertSame($connection, $pool->connection($url));
        $this->assertSame($connection, $pool->connection(new Url($url)));
    }

    protected function newPool()
    {
        return new ConnectionPool();
    }

}

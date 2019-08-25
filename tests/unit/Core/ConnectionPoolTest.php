<?php


namespace Ems\Core;

use Ems\Contracts\Core\ConnectionPool as ConnectionPoolContract;
use Ems\Core\Connection\GlobalsHttpInputConnection;
use Ems\Core\Connection\StdOutputConnection;
use Ems\Core\Support\StreamLogger;
use Psr\Log\LoggerInterface;

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

    /**
     * @expectedException Ems\Contracts\Core\Errors\NotFound
     **/
    public function test_connection_throws_exception_if_no_handler_found()
    {
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

    public function test_in_returns_bound_connection()
    {
        $pool = $this->newPool();
        $pool->extend(ConnectionPool::STDIN, function ($url) {
            return new GlobalsHttpInputConnection();
        });

        $in = $pool->in();
        $this->assertInstanceOf(GlobalsHttpInputConnection::class, $in);
        $this->assertSame($in, $pool->in());

    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function test_in_throws_exception_if_no_InputConnection()
    {
        $pool = $this->newPool();
        $pool->extend(ConnectionPool::STDIN, function ($url) {
            return new FilesystemConnection($url);
        });

        $pool->in();
    }

    public function test_out_returns_bound_connection()
    {
        $pool = $this->newPool();
        $pool->extend(ConnectionPool::STDOUT, function ($url) {
            return new StdOutputConnection();
        });

        $out = $pool->out();
        $this->assertInstanceOf(StdOutputConnection::class, $out);
        $this->assertSame($out, $pool->out());

    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function test_out_throws_exception_if_no_OutputConnection()
    {
        $pool = $this->newPool();
        $pool->extend(ConnectionPool::STDOUT, function ($url) {
            return new FilesystemConnection($url);
        });

        $pool->out();
    }

    public function test_log_returns_bound_logger()
    {
        $pool = $this->newPool();
        $pool->extend(ConnectionPool::STDERR, function ($url) {
            return new StreamLogger();
        });

        $logger = $pool->log();
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertSame($logger, $pool->log());

    }

    /**
     * @expectedException \Ems\Core\Exceptions\HandlerNotFoundException
     */
    public function test_log_throws_exception_if_no_handler_assigned()
    {
        $pool = $this->newPool();
        $pool->log();
    }

    /**
     * @expectedException \Ems\Contracts\Core\Exceptions\TypeException
     */
    public function test_log_throws_exception_if_handler_returns_no_LoggerInterface()
    {
        $pool = $this->newPool();
        $pool->extend(ConnectionPool::STDERR, function ($url) {
            return new FilesystemConnection($url);
        });

        $pool->log();

    }

    protected function newPool()
    {
        return new ConnectionPool();
    }

}

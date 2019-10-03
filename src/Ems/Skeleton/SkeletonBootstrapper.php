<?php
/**
 *  * Created by mtils on 25.08.19 at 08:49.
 **/

namespace Ems\Skeleton;


use Ems\Console\ConsoleInputConnection;
use Ems\Console\ConsoleOutputConnection;
use Ems\Contracts\Core\ConnectionPool as ConnectionPoolContract;
use Ems\Contracts\Core\InputConnection;
use Ems\Contracts\Core\IO;
use Ems\Contracts\Core\OutputConnection;
use Ems\Contracts\Core\Url;
use Ems\Core\Application;
use Ems\Core\Connection\GlobalsHttpInputConnection;
use Ems\Core\Connection\StdOutputConnection;
use Ems\Core\ConnectionPool;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Core\Support\StreamLogger;
use function file_exists;
use function getenv;
use function memory_get_peak_usage;
use function php_sapi_name;
use function register_shutdown_function;


class SkeletonBootstrapper extends Bootstrapper
{
    public function bind()
    {
        $this->app->bind(InputConnection::class, function () {
            /** @var ConnectionPoolContract $connectionPool */
            $connectionPool = $this->app->make(ConnectionPoolContract::class);
            return $connectionPool->connection(ConnectionPool::STDIN);
        });

        $this->app->bind(OutputConnection::class, function () {
            /** @var ConnectionPoolContract $connectionPool */
            $connectionPool = $this->app->make(ConnectionPoolContract::class);
            return $connectionPool->connection(ConnectionPool::STDOUT);
        });

        $this->app->afterResolving(ConnectionPool::class, function ($pool) {
            $this->addConnections($pool);
        });

        $this->installBenchmarkPrinter();
    }

    protected function addConnections(ConnectionPool $pool)
    {
        $pool->extend(ConnectionPool::STDIN, function (Url $url) {
            if (!$url->equals(ConnectionPool::STDIN)) {
                return null;
            }

            if (php_sapi_name() == 'cli') {
                return $this->app->make(ConsoleInputConnection::class);
            }

            return $this->app->make(GlobalsHttpInputConnection::class);
        });

        $pool->extend(ConnectionPool::STDOUT, function (Url $url) {
            if (!$url->equals(ConnectionPool::STDOUT)) {
                return null;
            }
            if (php_sapi_name() == 'cli') {
                return $this->app->make(ConsoleOutputConnection::class);
            }
            return $this->app->make(StdOutputConnection::class);
        });

        $pool->extend(ConnectionPool::STDERR, function (Url $url) {
            if ($url->equals(ConnectionPool::STDERR)) {
                return $this->createLogger();
            }
            return null;
        });
    }

    protected function createLogger()
    {
        /** @var Application $app */
        $app = $this->app->make('app');
        if ($app->environment() != 'production') {
            return new StreamLogger('php://stdout');
        }

        $logPath = $this->app->make('app')->path('local/log/app.log');

        if (!file_exists($logPath)) {
            return new StreamLogger('php://stderr');
        }

        return new StreamLogger($logPath);
    }

    protected function installBenchmarkPrinter()
    {
        if(!getenv('EMS_BENCHMARK')) {
            return;
        }

        register_shutdown_function(function () {
            /** @var IO $io */
            $io = $this->app->make(IO::class);
            $usage = memory_get_peak_usage(true);
            $usageOutput = $this->memoryFormat($usage);
            $io->log()->debug("Usage: $usageOutput");
        });
    }

    function memoryFormat($size) {
        $unit=array('B','KB','MB','GB','TB','PB');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
}
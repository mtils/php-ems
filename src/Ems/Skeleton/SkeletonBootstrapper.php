<?php
/**
 *  * Created by mtils on 25.08.19 at 08:49.
 **/

namespace Ems\Skeleton;


use Ems\Console\ConsoleInputConnection;
use Ems\Console\ConsoleOutputConnection;
use Ems\Contracts\Core\InputConnection;
use Ems\Contracts\Core\OutputConnection;
use Ems\Contracts\Core\Url;
use Ems\Core\Application;
use Ems\Core\Connection\GlobalsHttpInputConnection;
use Ems\Core\Connection\StdOutputConnection;
use Ems\Core\ConnectionPool;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Core\Support\StreamLogger;
use Ems\Routing\RoutedInputHandler;
use Ems\Testing\Benchmark;
use function defined;
use function file_exists;
use function getenv;
use function php_sapi_name;
use const APPLICATION_START;


class SkeletonBootstrapper extends Bootstrapper
{
    public function bind()
    {
        $this->app->bind(InputConnection::class, function () {
            return $this->createInputConnection();
        }, true);

        $this->app->bind(OutputConnection::class, function () {
            return $this->createOutputConnection();
        }, true);

        $this->app->afterResolving(ConnectionPool::class, function ($pool) {
            $this->addConnections($pool);
        });

        $this->installBenchmarkPrinter();
    }

    /**
     * @return InputConnection|object
     */
    protected function createInputConnection()
    {
        if (php_sapi_name() == 'cli') {
            return $this->app->make(ConsoleInputConnection::class);
        }

        return $this->app->make(GlobalsHttpInputConnection::class);
    }

    /**
     * @return OutputConnection|object
     */
    protected function createOutputConnection()
    {
        if (php_sapi_name() == 'cli') {
            return $this->app->make(ConsoleOutputConnection::class);
        }
        return $this->app->make(StdOutputConnection::class);
    }

    /**
     * @param ConnectionPool $pool
     */
    protected function addConnections(ConnectionPool $pool)
    {
        $pool->extend(ConnectionPool::STDIN, function (Url $url) {
            if (!$url->equals(ConnectionPool::STDIN)) {
                return null;
            }
            return $this->app->make(InputConnection::class);
        });

        $pool->extend(ConnectionPool::STDOUT, function (Url $url) {
            if (!$url->equals(ConnectionPool::STDOUT)) {
                return null;
            }
            return $this->app->make(OutputConnection::class);
        });

        $pool->extend(ConnectionPool::STDERR, function (Url $url) {
            if ($url->equals(ConnectionPool::STDERR)) {
                return $this->createLogger();
            }
            return null;
        });
    }

    /**
     * @return StreamLogger
     */
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

        if (defined('APPLICATION_START')) {
            Benchmark::raw(['name' => 'Application Start', 'time' => APPLICATION_START]);
        }

        /** @var Application $app */
        $app = $this->app->make('app');
        $app->onAfter('boot', function() {
            Benchmark::mark('Booted');
        });

        $this->app->afterResolving(RoutedInputHandler::class, function (RoutedInputHandler $handler) {
            $handler->onBefore('call', function () {
                Benchmark::mark('Routed');
            });
            $handler->onAfter('call', function () {
                Benchmark::mark('Performed');
            });
        });
    }

}
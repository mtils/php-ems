<?php
/**
 *  * Created by mtils on 25.08.19 at 08:49.
 **/

namespace Ems\Skeleton;


use Ems\Contracts\Core\Url;
use Ems\Contracts\Skeleton\InputConnection;
use Ems\Contracts\Skeleton\OutputConnection;
use Ems\Core\ConnectionPool;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Model\Database\DB;
use Ems\Routing\RoutedInputHandler;
use Ems\Testing\Benchmark;
use Psr\Log\LoggerInterface;

use function defined;
use function file_exists;
use function getenv;
use function spl_object_hash;

use const APPLICATION_START;


class SkeletonBootstrapper extends Bootstrapper
{
    protected $aliases = [
        LoggerInterface::class => [StreamLogger::class]
    ];

    /**
     * @var array
     */
    protected $configuredPools = [];

    public function bind()
    {
        $this->app->share(LoggerInterface::class, function () {
            return $this->createLogger();
        });

        $this->app->onAfter(ConnectionPool::class, function ($pool) {
            $poolId = spl_object_hash($pool);
            if (isset($this->configuredPools[$poolId])) {
                return;
            }
            $this->addConnections($pool);
            $this->configuredPools[$poolId] = true;
        });

        $this->installBenchmarkPrinter();
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
            return $this->app->get(InputConnection::class);
        });

        $pool->extend(ConnectionPool::STDOUT, function (Url $url) {
            if (!$url->equals(ConnectionPool::STDOUT)) {
                return null;
            }
            return $this->app->get(OutputConnection::class);
        });

        $pool->extend(ConnectionPool::STDERR, function (Url $url) {
            if ($url->equals(ConnectionPool::STDERR)) {
                return $this->app->get(StreamLogger::class);
            }
            return null;
        });

        /** @var Application $app */
        $app = $this->app->get(Application::class);
        if (!$databaseConfig = $app->config('database')) {
            return;
        }

        $handler = DB::makeConnectionHandler(
            DB::configurationsToUrls($databaseConfig['connections']),
            $databaseConfig
        );
        $pool->extend('config.database', $handler);

    }

    /**
     * @return StreamLogger
     */
    protected function createLogger()
    {
        /** @var Application $app */
        $app = $this->app->get('app');

        if ($app->environment() == Application::TESTING) {
            return new StreamLogger('php://stdout');
        }

        $logPath = $app->path('local/log/app.log');

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
        $app = $this->app->get('app');
        $app->onAfter('boot', function() {
            Benchmark::mark('Booted');
        });

        $this->app->onAfter(RoutedInputHandler::class, function (RoutedInputHandler $handler) {
            $handler->onBefore('call', function () {
                Benchmark::mark('Routed');
            });
            $handler->onAfter('call', function () {
                Benchmark::mark('Performed');
            });
        });
    }

}
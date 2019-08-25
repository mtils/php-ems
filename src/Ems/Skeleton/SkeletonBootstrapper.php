<?php
/**
 *  * Created by mtils on 25.08.19 at 08:49.
 **/

namespace Ems\Skeleton;


use Ems\Contracts\Core\Url;
use Ems\Core\Application;
use Ems\Core\Connection\GlobalsHttpInputConnection;
use Ems\Core\Connection\StdOutputConnection;
use Ems\Core\ConnectionPool;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Core\Support\StreamLogger;
use function file_exists;

class SkeletonBootstrapper extends Bootstrapper
{
    public function bind()
    {
        $this->app->afterResolving(ConnectionPool::class, function ($pool) {
            $this->addConnections($pool);
        });
    }

    protected function addConnections(ConnectionPool $pool)
    {
        $pool->extend(ConnectionPool::STDIN, function (Url $url) {
            if ($url->equals(ConnectionPool::STDIN)) {
                return new GlobalsHttpInputConnection();
            }
        });

        $pool->extend(ConnectionPool::STDOUT, function (Url $url) {
            if ($url->equals(ConnectionPool::STDOUT)) {
                return new StdOutputConnection();
            }
        });

        $pool->extend(ConnectionPool::STDERR, function (Url $url) {
            if ($url->equals(ConnectionPool::STDERR)) {
                return $this->createLogger();
            }
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
}
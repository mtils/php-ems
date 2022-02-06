<?php
/**
 *  * Created by mtils on 25.08.19 at 08:49.
 **/

namespace Ems\Skeleton;


use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Skeleton\InputConnection;
use Ems\Contracts\Skeleton\OutputConnection;
use Ems\Core\ConnectionPool;
use Ems\Core\Response;
use Ems\Model\Database\DB;
use Ems\Routing\HttpInput;
use Ems\Routing\InputHandler;
use Ems\Routing\RoutedInputHandler;
use Ems\Testing\Benchmark;
use Ems\Testing\BenchmarkRenderer\JSConsoleRenderer;
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
        $this->container->share(LoggerInterface::class, function () {
            return $this->createLogger();
        });

        $this->container->onAfter(ConnectionPool::class, function ($pool) {
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
            return $this->container->get(InputConnection::class);
        });

        $pool->extend(ConnectionPool::STDOUT, function (Url $url) {
            if (!$url->equals(ConnectionPool::STDOUT)) {
                return null;
            }
            return $this->container->get(OutputConnection::class);
        });

        $pool->extend(ConnectionPool::STDERR, function (Url $url) {
            if ($url->equals(ConnectionPool::STDERR)) {
                return $this->container->get(StreamLogger::class);
            }
            return null;
        });

        if (!$databaseConfig = $this->app->config('database')) {
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
        if ($this->app->environment() == Application::TESTING) {
            return new StreamLogger('php://stdout');
        }

        $logPath = $this->app->path('local/log/app.log');

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

        $this->container->onAfter(InputHandler::class, function (InputHandler $handler) {
            $middlewares = $handler->middleware();
            $middlewares->add('benchmark', function (Input $input, callable $next) {
                if (!$input instanceof HttpInput) {
                    return $next($input);
                }
                /** @var Response $response */
                $response = $next($input);
                $renderer = new JSConsoleRenderer();
                $benchResult = $renderer->render(Benchmark::instance());
                $payload = $response->payload;
                if (!Type::isStringable($payload)) {
                    return $response;
                }
                $output = $response->payload instanceof Stringable ? $response->payload->toString() : (string)$response->payload;
                return $response->withPayload(str_replace('</body>', "$benchResult\n</body>", $output));
            })->after('handle');
        });

        if (defined('APPLICATION_START')) {
            Benchmark::raw(['name' => 'Application Start', 'time' => APPLICATION_START]);
        }

        $this->app->onAfter('boot', function() {
            Benchmark::mark('Booted');
        });

        $this->container->onAfter(RoutedInputHandler::class, function (RoutedInputHandler $handler) {
            $handler->onBefore('call', function () {
                Benchmark::mark('Routed');
            });
            $handler->onAfter('call', function () {
                Benchmark::mark('Performed');
            });
        });
    }

}
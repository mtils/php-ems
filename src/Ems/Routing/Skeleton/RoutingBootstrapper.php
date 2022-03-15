<?php
/**
 *  * Created by mtils on 10.08.19 at 19:07.
 **/

namespace Ems\Routing\Skeleton;


use Ems\Console\AnsiRenderer;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\InputHandler as InputHandlerContract;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Ems\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Routing\ConsoleDispatcher;
use Ems\Routing\FastRoute\FastRouteDispatcher;
use Ems\Routing\HttpInput;
use Ems\Routing\InputHandler;
use Ems\Routing\MiddlewareCollection;
use Ems\Routing\ResponseFactory;
use Ems\Routing\RoutedInputHandler;
use Ems\Routing\RouteMiddleware;
use Ems\Routing\Router;
use Ems\Routing\SessionHandler\FileSessionHandler;
use Ems\Routing\SessionMiddleware;
use Ems\Skeleton\Bootstrapper;
use Ems\Skeleton\Routing\RoutesConsoleView;
use Ems\Skeleton\Routing\RoutesController;
use Psr\Http\Message\RequestInterface;
use Ems\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Ems\Routing\UrlGenerator;

use function method_exists;
use function php_sapi_name;

class RoutingBootstrapper extends Bootstrapper
{
    protected $singletons = [
        InputHandler::class         => InputHandlerContract::class,
        Router::class               => RouterContract::class,
        ResponseFactory::class      => ResponseFactoryContract::class
    ];

    protected $bindings = [
        FastRouteDispatcher::class => Dispatcher::class,
        MiddlewareCollection::class => MiddlewareCollectionContract::class,
        UrlGenerator::class         => UrlGeneratorContract::class
    ];

    protected $defaultSessionClients = [Input::CLIENT_WEB, Input::CLIENT_CMS, Input::CLIENT_AJAX, Input::CLIENT_MOBILE];

    public function bind()
    {
        parent::bind();

        $this->container->onAfter(ConsoleDispatcher::class, function (ConsoleDispatcher $dispatcher) {
            $dispatcher->setFallbackCommand('commands');
        });

        $this->container->onAfter(InputHandler::class, function (InputHandler $handler) {
            $this->addDefaultMiddleware($handler->middleware());
        });

        $this->container->on(AnsiRenderer::class, function (AnsiRenderer $renderer) {
            /** @noinspection PhpParamsInspection */
            $renderer->extend('routes.index', $this->container->create(RoutesConsoleView::class));
        });
        $this->addDefaultRoutes();

    }

    protected function addDefaultMiddleware(MiddlewareCollectionContract $collection)
    {

        $this->addClientTypeMiddleware($collection);
        $this->addRouteScopeMiddleware($collection);
        $this->addSessionMiddleware($collection);
        $this->addRouterToMiddleware($collection);
        $this->addRouteMiddleware($collection);
        $this->addRouteHandlerMiddleware($collection);
    }

    protected function addClientTypeMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('client-type', function (Input $input, callable $next) {

            if ($input->getClientType() || !$input instanceof HttpInput) {
                return $next($input);
            }

            if (php_sapi_name() == 'cli') {
                return $next($input->withClientType(Input::CLIENT_CONSOLE));
            }
            if (!$url = $input->getUrl()) {
                return $next($input->withClientType(Input::CLIENT_WEB));
            }
            if ($url->path->first() == 'api') {
                /** @var RequestInterface|Input $input */
                $input = $input->withClientType(Input::CLIENT_API)
                               ->withUrl($url->shift());
                return $next($input);
            }
            return $next($input->withClientType(Input::CLIENT_WEB));
        });
    }

    protected function addRouteScopeMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('route-scope', function (Input $input, callable $next) {
            if (method_exists($input, 'setRouteScope')) {
                $input->setRouteScope('default');
                return $next($input);
            }
            if (method_exists($input, 'withRouteScope')) {
                $input = $input->withRouteScope('default');
            }
            return $next($input);
        });
    }

    protected function addSessionMiddleware(MiddlewareCollectionContract $collection)
    {
        $config = $this->app->config('session');
        $clientTypes = $config['clients'] ?? $this->defaultSessionClients;
        $collection->add('session', SessionMiddleware::class)->clientType(...$clientTypes);

        if (!$config) {
            return;
        }

        $this->app->on(SessionMiddleware::class, function (SessionMiddleware $middleware) use ($config) {
            $this->configureSessionMiddleware($middleware, $config);
        });
    }

    protected function addRouterToMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('router', RouterContract::class);
    }

    protected function addRouteMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('route-middleware', RouteMiddleware::class);
    }

    protected function addRouteHandlerMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('handle', RoutedInputHandler::class);
    }

    protected function configureSessionMiddleware(SessionMiddleware $middleware, array $config)
    {
        $middleware->extend('files', function () use ($config) {
            $path = $config['path'] ?? $this->appPath() . '/local/storage/sessions';
            /** @var FileSessionHandler $handler */
            $handler = $this->container->create(FileSessionHandler::class);
            $handler->setPath($path);
            return $handler;
        });


        if (isset($config['cookie'])) {
            $middleware->setCookieConfig($config['cookie']);
        }
        if (isset($config['driver']) && $config['driver']) {
            $middleware->setDriver($config['driver']);
        }
        if (isset($config['driver']) && $config['driver']) {
            $middleware->setDriver($config['driver']);
        }
        if (isset($config['serverside_lifetime']) && $config['serverside_lifetime']) {
            $middleware->setLifeTime((int)$config['serverside_lifetime']);
        }
    }

    protected function addDefaultRoutes()
    {
        $this->addRoutesBy(function (RouteCollector $routes) {

            $routes->command(
                'commands',
                ConsoleCommandsController::class.'->index',
                'List all of your console commands.'
            )->argument('?pattern', 'List only commands matching this pattern');

            $routes->command(
                'help',
                ConsoleCommandsController::class.'->show',
                'Show help for one console command.'
            )->argument('command_name', 'The name of the command you need help for.');

            $routes->command(
                'routes',
                RoutesController::class.'->index',
                'List all your routes (and commands)'
            )->argument('?columns', 'What columns to show? v=Verb(Method), p=Pattern, n=Name, c=Clients, s=Scopes, m=Middleware')
             ->option('pattern=*', 'Routes matches this pattern', 'p')
             ->option('client=*', 'Routes of this client types', 'c')
             ->option('name=*', 'Routes with this name', 'n')
             ->option('scope=*', 'Routes of this scope', 's');
        });
    }
}
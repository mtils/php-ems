<?php
/**
 *  * Created by mtils on 10.08.19 at 19:07.
 **/

namespace Ems\Routing\Skeleton;


use Ems\Contracts\Core\Input;
use Ems\Contracts\Routing\InputHandler as InputHandlerContract;
use Ems\Contracts\Core\ResponseFactory as ResponseFactoryContract;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\ResponseFactory;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Routing\ConsoleDispatcher;
use Ems\Routing\FastRoute\FastRouteDispatcher;
use Ems\Routing\InputHandler;
use Ems\Routing\MiddlewareCollection;
use Ems\Routing\RoutedInputHandler;
use Ems\Routing\RouteMiddleware;
use Ems\Routing\Router;

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
    ];

    public function bind()
    {
        parent::bind();

        $this->app->bind(Input::class, function () {

        });

        $this->app->onAfter(ConsoleDispatcher::class, function (ConsoleDispatcher $dispatcher) {
            $dispatcher->setFallbackCommand('commands');
        });

        $this->app->onAfter(InputHandler::class, function (InputHandler $handler) {
            $this->addDefaultMiddleware($handler->middleware());
        });

    }

    protected function addDefaultMiddleware(MiddlewareCollectionContract $collection)
    {
        $this->addClientTypeMiddleware($collection);
        $this->addRouteScopeMiddleware($collection);
        $this->addRouterToMiddleware($collection);
        $this->addRouteMiddleware($collection);
        $this->addRouteHandlerMiddleware($collection);
    }

    protected function addClientTypeMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('client-type', function (Input $input, callable $next) {
            if ($input->clientType()) {
                return $next($input);
            }
            if (php_sapi_name() == 'cli') {
                $input->setClientType(Routable::CLIENT_CONSOLE);
                return $next($input);
            }
            if (!$url = $input->url()) {
                $input->setClientType(Routable::CLIENT_WEB);
                return $next($input);
            }
            if ($url->path->first() == 'api') {
                $input->setClientType(Routable::CLIENT_API);
                $input->setUrl($input->url()->shift());
                return $next($input);
            }
            $input->setClientType(Routable::CLIENT_WEB);
            return $next($input);
        });
    }

    protected function addRouteScopeMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('route-scope', function (Input $input, callable $next) {
            $input->setRouteScope('default');
            return $next($input);
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
}
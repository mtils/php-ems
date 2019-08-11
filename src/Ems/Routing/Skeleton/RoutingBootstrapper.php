<?php
/**
 *  * Created by mtils on 10.08.19 at 19:07.
 **/

namespace Ems\Routing\Skeleton;


use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\InputHandler as InputHandlerContract;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Ems\Contracts\Routing\Routable;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Routing\FastRoute\FastRouteDispatcher;
use Ems\Routing\InputHandler;
use Ems\Routing\MiddlewareCollection;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Routing\RoutedInputHandler;
use Ems\Routing\Router;
use function php_sapi_name;

class RoutingBootstrapper extends Bootstrapper
{
    protected $singletons = [
        MiddlewareCollection::class => MiddlewareCollectionContract::class,
        InputHandler::class         => InputHandlerContract::class,
        Router::class               => RouterContract::class
    ];

    protected $bindings = [
        FastRouteDispatcher::class => Dispatcher::class
    ];

    public function bind()
    {
        parent::bind();
        $this->app->afterResolving(InputHandler::class, function (InputHandler $handler) {
            $this->addDefaultMiddleware($handler->middleware());
        });

    }

    protected function addDefaultMiddleware(MiddlewareCollectionContract $collection)
    {
        $this->addClientTypeMiddleware($collection);
        $this->addRouteScopeMiddleware($collection);
        $this->addRouterMiddleware($collection);
        $this->addRouteHandlerMiddleware($collection);
    }

    protected function addClientTypeMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('client-type', function (Input $input, callable $next) {
            if ($input->clientType()) {
                return $next($input);
            }
            $clientType = php_sapi_name() == 'cli' ? Routable::CLIENT_CONSOLE : Routable::CLIENT_WEB;
            $input->setClientType($clientType);
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

    protected function addRouterMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('router', RouterContract::class);
    }

    protected function addRouteHandlerMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('handle', RoutedInputHandler::class);
    }
}
<?php
/**
 *  * Created by mtils on 10.08.19 at 19:07.
 **/

namespace Ems\Routing\Skeleton;


use Ems\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\InputHandler as InputHandlerContract;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Routing\ResponseFactory;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Routing\ConsoleDispatcher;
use Ems\Routing\FastRoute\FastRouteDispatcher;
use Ems\Routing\HttpInput;
use Ems\Routing\InputHandler;
use Ems\Routing\MiddlewareCollection;
use Ems\Routing\RoutedInputHandler;
use Ems\Routing\RouteMiddleware;
use Ems\Routing\Router;

use Psr\Http\Message\RequestInterface;

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
                               ->withUri($input->getUrl()->shift());
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
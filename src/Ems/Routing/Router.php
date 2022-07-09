<?php
/**
 *  * Created by mtils on 30.06.19 at 11:07.
 **/

namespace Ems\Routing;

use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Routing\Command;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\Lambda;
use Ems\Core\Response;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Routing\FastRoute\FastRouteDispatcher;
use ReflectionException;

use function call_user_func;

class Router implements RouterContract, SupportsCustomFactory
{
    use CustomFactorySupport;

    /**
     * @var Dispatcher[]
     */
    protected $dispatchers = [];

    /**
     * @var Dispatcher[]
     */
    protected $configuredDispatchers = [];

    /**
     * @var callable
     */
    protected $dispatcherFiller;

    /**
     * @var callable
     */
    protected $dispatcherFactory;

    /**
     * The divider between middleware name and parameters
     * @param callable|null $dispatcherFactory
     */
    public function __construct(callable $dispatcherFactory=null)
    {
        $this->installDispatcherFactory($dispatcherFactory);
        $this->dispatcherFiller = function (Dispatcher $dispatcher, string $clientType) {
            // Do nothing
        };
    }

    /**
     * {@inheritDoc}
     *
     * @param Input $routable
     *
     * @return Input
     *
     * @throws ReflectionException
     */
    public function route(Input $routable) : Input
    {
        $clientType = $routable->getClientType() ?: Input::CLIENT_WEB;
        $dispatcher = $this->getConfiguredDispatcher($clientType);
        $hit = $dispatcher->match($routable->getMethod(), (string)$routable->getUrl()->path);
        $routeData = $hit->handler;

        $scope = $routable->getRouteScope();

        if ($routeData['scopes'] && $routeData['scopes'] != ['*'] && !in_array((string)$scope, $routeData['scopes'])) {
            throw new RouteNotFoundException("Route {$hit->pattern} is not allowed in scope $scope");
        }

        $parameters = $this->buildParameters($routeData['defaults'], $hit->parameters);

        $route = new Route($routeData['methods'], $routeData['pattern'], $routeData['handler']);
        $route->scope($routeData['scopes'])
              ->clientType($routeData['clientTypes'])
              ->middleware($routeData['middlewares'])
              ->defaults($routeData['defaults'])
              ->name($routeData['name']);

        if (isset($routeData['command']) && $routeData['command'] instanceof Command) {
            $route->command($routeData['command']);
        }

        return $routable->makeRouted($route, $this->makeHandler($routable, $route), $parameters);

    }

    /**
     * {@inheritDoc}
     *
     * @param string $clientType
     *
     * @return Dispatcher
     */
    public function getDispatcher(string $clientType) : Dispatcher
    {
        if (!isset($this->dispatchers[$clientType])) {
            $this->dispatchers[$clientType] = call_user_func($this->dispatcherFactory, $clientType);
        }
        return $this->dispatchers[$clientType];
    }

    /**
     * Use the router as normal middleware.
     *
     * @param Input $input
     * @param callable $next
     *
     * @return Response
     * @throws ReflectionException
     */
    public function __invoke(Input $input, callable $next)
    {
        return $next($this->route($input));
    }

    /**
     * Assign a callable that will fill the dispatcher once before it is used.
     * Here you can defer loading of routes to when they needed.
     * The callable will be called by the dispatcher and clientType:
     * function (Dispatcher $dispatcher, string $clientType) {
     *    $dispatcher->add();
     * }
     *
     * @param callable $filler
     * @return self
     */
    public function fillDispatchersBy(callable $filler) : Router
    {
        $this->dispatcherFiller = $filler;
        return $this;
    }

    /**
     * Assign the callable that will create the dispatchers
     *
     * @param callable|null $factory (optional)
     */
    protected function installDispatcherFactory(callable $factory=null)
    {
        $this->dispatcherFactory = $factory ?: function ($clientType) {
            if (in_array($clientType, [Input::CLIENT_CONSOLE, Input::CLIENT_TASK])) {
                return $this->createObject(ConsoleDispatcher::class);
            }
            return $this->createObject(FastRouteDispatcher::class);
        };
    }

    /**
     * Merge default and calculated parameters,
     *
     * @param array $defaults
     * @param array $routeParameters
     *
     * @return array
     */
    protected function buildParameters(array $defaults, array $routeParameters) : array
    {
        foreach ($defaults as $key=>$value) {
            if (!isset($routeParameters[$key])) {
                $routeParameters[$key] = $value;
            }
        }
        return $routeParameters;
    }

    /**
     * @param Input  $input
     * @param Route  $route
     *
     * @return Lambda
     *
     * @throws ReflectionException
     */
    protected function makeHandler(Input $input, Route $route) : Lambda
    {
        $lambda = new Lambda($route->handler, $this->_customFactory);

        if ($this->_customFactory) {
            $lambda->autoInject(true, false);
        }

        return $lambda;
    }

    /**
     * Get the dispatcher and make sure it is already configured.
     *
     * @param string $clientType
     * @return Dispatcher
     */
    protected function getConfiguredDispatcher(string $clientType) : Dispatcher
    {
        if (isset($this->configuredDispatchers[$clientType])) {
            return $this->configuredDispatchers[$clientType];
        }

        $dispatcher = $this->getDispatcher($clientType);
        call_user_func($this->dispatcherFiller, $dispatcher, $clientType);
        $this->configuredDispatchers[$clientType] = $dispatcher;
        return $dispatcher;

    }

}
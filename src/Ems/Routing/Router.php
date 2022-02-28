<?php
/**
 *  * Created by mtils on 30.06.19 at 11:07.
 **/

namespace Ems\Routing;

use ArrayIterator;
use Ems\Contracts\Core\Containers\ByTypeContainer;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Routing\Command;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Lambda;
use Ems\Core\Response;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Routing\FastRoute\FastRouteDispatcher;
use OutOfBoundsException;
use ReflectionException;
use Traversable;

use function call_user_func;
use function get_class;
use function implode;
use function is_object;

class Router implements RouterContract, SupportsCustomFactory
{
    use CustomFactorySupport;

    /**
     * @var array
     */
    protected $allRoutes = [];

    /**
     * @var array
     */
    protected $byName = [];

    /**
     * @var array
     */
    protected $byPattern = [];

    /**
     * @var array
     */
    protected $byEntity = [];

    /**
     * @var ByTypeContainer[]
     */
    protected $byTypeContainers = [];

    /**
     * @var array
     */
    protected $dispatchers = [];

    /**
     * @var callable
     */
    protected $interpreterFactory;

    /**
     * The divider between middleware name and parameters
     * @var string
     */
    public function __construct()
    {
        $this->installInterpreterFactory();
    }

    /**
     * @param callable $registrar
     * @param array    $attributes (optional)
     */
    public function register(callable $registrar, array $attributes=[])
    {

        $collector = $this->newCollector($attributes);
        $registrar($collector);

        // Cast to Route[] :-)
        /** @var Route[] $collector */
        foreach ($collector as $route) {
            $this->addRoute($route);
        }

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
        $interpreter = $this->getDispatcher($clientType);
        $hit = $interpreter->match($routable->getMethod(), (string)$routable->getUrl()->path);
        $routeData = $hit->handler;

        $scope = $routable->getRouteScope();

        if ($routeData['scopes'] != ['*'] && !in_array((string)$scope, $routeData['scopes'])) {
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
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->allRoutes);
    }

    /**
     * {@inheritDoc}
     *
     * @param string        $pattern
     * @param string|null   $method
     * @param string        $clientType
     *
     * @return Route[]
     */
    public function getByPattern(string $pattern, string $method=null, string $clientType=Input::CLIENT_WEB) : array
    {
        if (!isset($this->byPattern[$clientType][$pattern])) {
            return [];
        }

        if (!$method) {
            return $this->byPattern[$clientType][$pattern];
        }

        $result = [];

        /** @var Route $route */
        foreach ($this->byPattern[$clientType][$pattern] as $route) {
            if (in_array($method, $route->methods)) {
                $result[] = $route;
            }
        }

        return $result;
    }

    /**
     * Get a route by its name.
     *
     * @param string $name
     * @param string $clientType
     *
     * @return Route
     */
    public function getByName(string $name, string $clientType=Input::CLIENT_WEB) : Route
    {
        if (isset($this->byName[$clientType][$name])) {
            return $this->byName[$clientType][$name];
        }
        throw new KeyNotFoundException("Route named '$name' not found for clientType '$clientType'.");
    }

    /**
     * {@inheritDoc}
     *
     * @param object|string $entity
     * @param string $action
     * @param string $clientType
     *
     * @return Route
     */
    public function getByEntityAction($entity, string $action = 'index', string $clientType = Input::CLIENT_WEB): Route
    {
        if (is_string($entity) && isset($this->byEntity[$clientType][$entity][$action])) {
            return $this->byEntity[$clientType][$entity][$action];
        }
        if (!isset($this->byTypeContainers[$clientType])) {
            $this->byTypeContainers[$clientType] = new ByTypeContainer($this->byEntity[$clientType]);
        }
        $class = is_object($entity) ? get_class($entity) : $entity;
        if (!$result = $this->byTypeContainers[$clientType]->forInstanceOf($class)) {
            throw new OutOfBoundsException("No route found that was associated with entity '$class'.");
        }

        if (isset($result[$action])) {
            return $result[$action];
        }

        $actions = array_keys($result);
        throw new OutOfBoundsException("Action $action not found for entity '$class'. The only known actions are: " . implode(',', $actions));

    }


    /**
     * Return all known unique client types (by route registrations)
     *
     * @return string[]
     */
    public function clientTypes() : array
    {
        return array_keys($this->byPattern);
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
            $this->dispatchers[$clientType] = call_user_func($this->interpreterFactory, $clientType);
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
     * Add a route to all places were it is needed.
     *
     * @param Route   $route
     */
    protected function addRoute(Route $route)
    {

        if (!$route->clientTypes) {
            $route->clientType(Input::CLIENT_WEB);
        }
        if (!$route->scopes) {
            $route->scope('default');
        }

        $data = $route->toArray();

        $this->allRoutes[] = $route;

        foreach ($data['clientTypes'] as $clientType) {
            $interpreter = $this->getDispatcher($clientType);

            foreach ($data['methods'] as $method) {
                $interpreter->add($method, $data['pattern'], $data);
            }

            if (!isset($this->byPattern[$clientType])) {
                $this->byPattern[$clientType] = [];
            }

            if (!isset($this->byPattern[$clientType][$data['pattern']])) {
                $this->byPattern[$clientType][$data['pattern']] = [];
            }

            $this->byPattern[$clientType][$data['pattern']][] = $route;

            if (!isset($data['entity']) || !$data['entity']) {
                continue;
            }

            if (!isset($this->byEntity[$clientType])) {
                $this->byEntity[$clientType] = [
                    $data['entity'] => []
                ];
            }
            $this->byEntity[$clientType][$data['entity']][$data['action']] = $route;

        }

        if (!$data['name']) {
            return;
        }

        foreach ($data['clientTypes'] as $clientType) {
            if (!isset($this->byName[$clientType])) {
                $this->byName[$clientType] = [];
            }
            $this->byName[$clientType][$data['name']] = $route;
        }

    }

    /**
     * Assign the callable that will create the interpreters.
     *
     * @param callable|null $factory (optional)
     */
    protected function installInterpreterFactory(callable $factory=null)
    {
        $this->interpreterFactory = $factory ?: function ($clientType) {
            if (in_array($clientType, [Input::CLIENT_CONSOLE, Input::CLIENT_TASK])) {
                return $this->createObject(ConsoleDispatcher::class);
            }
            return $this->createObject(FastRouteDispatcher::class);
        };
    }

    /**
     * Create the collector, which is usually the object registering your routes
     *
     * @param array $attributes (optional)
     *
     * @return RouteCollector
     */
    protected function newCollector($attributes=[])
    {
        return new RouteCollector($attributes);
    }

    /**
     * Merge default and calculated parameters,
     *
     * @param array $defaults
     * @param array $routeParameters
     *
     * @return array
     */
    protected function buildParameters(array $defaults, array $routeParameters)
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

}
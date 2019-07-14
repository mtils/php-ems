<?php
/**
 *  * Created by mtils on 30.06.19 at 11:07.
 **/

namespace Ems\Routing;

use ArrayIterator;
use Ems\Contracts\Routing\Dispatcher as DispatcherContract;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Interpreter;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Url;
use Ems\Routing\FastRoute\FastRouteInterpreter;
use Traversable;
use function call_user_func;

class Dispatcher implements DispatcherContract
{
    /**
     * @var array
     */
    protected $allRoutes = [];

    /**
     * @var array
     */
    protected $byScope = [];

    /**
     * @var array
     */
    protected $byClientType = [];

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
    protected $dispatchers = [];

    /**
     * @var callable
     */
    protected $interpreterFactory;

    public function __construct(Dispatcher $parent=null, $allRoutes=[])
    {
        $this->addRoutes($allRoutes);
        $this->installInterpreterFactory();
    }

    /**
     * @param callable $registrar
     * @return mixed
     */
    public function register(callable $registrar)
    {
        $collector = $this->newCollector();
        $registrar($collector);
        $this->addRoutes($collector);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $clientType
     * @param string $scope
     *
     * @return Routable
     */
    public function dispatch($method, $uri, $clientType=Routable::CLIENT_WEB, $scope='default')
    {
        $interpreter = $this->getInterpreter($clientType);
        $hit = $interpreter->match($method, $uri);
        $routeData = $hit->handler;

        if ($routeData['scopes'] != ['*'] && !in_array((string)$scope, $routeData['scopes'])) {
            throw new RouteNotFoundException("Route {$hit->pattern} is not allowed in scope $scope");
        }

        $routable = new RoutableData();
        $routable->setClientType($clientType);
        $routable->setRouteScope($scope);
        $routable->setMethod($method);


        $routable->setRouteParameters($this->buildParameters($routeData['defaults'], $hit->parameters));

        $route = new Route($routeData['methods'],$routeData['pattern'], $routeData['handler']);
        $route->scope($routeData['scopes'])
              ->clientType($routeData['clientTypes'])
              ->middleware($routeData['middlewares'])
              ->defaults($routeData['defaults'])
              ->name($routeData['name']);

        $routable->setMatchedRoute($route);
        $routable->setUrl(new Url($uri));

        return $routable;
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
     * @param string $pattern
     * @param string $method (optional)
     *
     * @return Route[]
     */
    public function getByPattern($pattern, $method=null)
    {
        if (!isset($this->byPattern[$pattern])) {
            return [];
        }

        if (!$method) {
            return $this->byPattern[$pattern];
        }

        $result = [];

        /** @var Route $route */
        foreach ($this->byPattern[$pattern] as $route) {
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
     *
     * @return Route
     */
    public function getByName($name)
    {
        if (isset($this->byName[$name])) {
            return $this->byName[$name];
        }
        throw new KeyNotFoundException("Route named '$name' not found.");
    }

    /**
     * @param Route[] $routes
     */
    protected function addRoutes($routes)
    {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }
    }

    /**
     * Add a route to all places were it is needed.
     *
     * @param Route $route
     */
    protected function addRoute(Route $route)
    {
        $data = $route->toArray();

        if (!$data['clientTypes']) {
            $data['clientTypes'] = ['web'];
        }

        if (!$data['scopes']) {
            $data['scopes'] = ['default'];
        }

        $this->allRoutes[] = $route;

        foreach ($data['clientTypes'] as $clientType) {

            $interpreter = $this->getInterpreter($clientType);

            foreach ($data['methods'] as $method) {
                $interpreter->add($method, $data['pattern'], $data);
            }

        }

        if (!isset($this->byPattern[$data['pattern']])) {
            $this->byPattern[$data['pattern']] = [];
        }

        $this->byPattern[$data['pattern']][] = $route;

        if (!$data['name']) {
            return;
        }

        $this->byName[$data['name']] = $route;

    }

    /**
     * @param string $clientType
     *
     * @return Interpreter
     */
    protected function getInterpreter($clientType)
    {
        if (!isset($this->dispatchers[$clientType])) {
            $this->dispatchers[$clientType] = call_user_func($this->interpreterFactory, $clientType);
        }
        return $this->dispatchers[$clientType];
    }

    /**
     * Assign the callable that will create the interpreters.
     *
     * @param callable $factory (optional)
     */
    protected function installInterpreterFactory(callable $factory=null)
    {
        $this->interpreterFactory = $factory ?: function () {
            return new FastRouteInterpreter();
        };
    }

    /**
     * Create the collector, which is usually the object registering your routes
     *
     * @return RouteCollector
     */
    protected function newCollector()
    {
        return new RouteCollector();
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
}
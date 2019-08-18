<?php
/**
 *  * Created by mtils on 30.06.19 at 11:07.
 **/

namespace Ems\Routing;

use ArrayIterator;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Lambda;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Routing\FastRoute\FastRouteDispatcher;
use ReflectionException;
use Traversable;
use function call_user_func;

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
     * @param Routable $routable
     *
     * @throws ReflectionException
     */
    public function route(Routable $routable)
    {
        $interpreter = $this->getInterpreter($routable->clientType());
        $hit = $interpreter->match($routable->method(), (string)$routable->url()->path);
        $routeData = $hit->handler;

        $scope = $routable->routeScope();

        if ($routeData['scopes'] != ['*'] && !in_array((string)$scope, $routeData['scopes'])) {
            throw new RouteNotFoundException("Route {$hit->pattern} is not allowed in scope $scope");
        }

        $routable->setRouteParameters($this->buildParameters($routeData['defaults'], $hit->parameters));

        $route = new Route($routeData['methods'], $routeData['pattern'], $routeData['handler']);
        $route->scope($routeData['scopes'])
              ->clientType($routeData['clientTypes'])
              ->middleware($routeData['middlewares'])
              ->defaults($routeData['defaults'])
              ->name($routeData['name']);

        $routable->setMatchedRoute($route);

        $routable->setHandler($this->makeHandler($routable));

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
        $this->route($input);
        return $next($input);
    }

    /**
     * Add a route to all places were it is needed.
     *
     * @param Route   $route
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
     * @return Dispatcher
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
            return new FastRouteDispatcher();
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
     * @param Routable  $routeData
     *
     * @return Lambda
     *
     * @throws ReflectionException
     */
    protected function makeHandler(Routable $routeData)
    {
        $handler = $routeData->matchedRoute()->handler;

        $lambda = new Lambda($handler, $this->_customFactory);

        if ($this->_customFactory) {
            $lambda->autoInject(true, false);
        }

        return $lambda;
    }

}
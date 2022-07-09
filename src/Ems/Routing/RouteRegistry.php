<?php
/**
 *  * Created by mtils on 15.06.2022 at 14:11.
 **/

namespace Ems\Routing;

use ArrayIterator;
use Ems\Contracts\Core\Containers\ByTypeContainer;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Contracts\Routing\RouteRegistry as RouteRegistryContract;
use Ems\Core\Exceptions\KeyNotFoundException;
use OutOfBoundsException;
use Traversable;

use function array_keys;
use function func_num_args;
use function get_class;
use function implode;
use function in_array;
use function is_iterable;
use function is_object;
use function is_string;
use function iterator_to_array;

use const E_USER_WARNING;

class RouteRegistry implements RouteRegistryContract
{
    public const KEY_VALID = 'routes-cached';

    protected const KEY_ALL = 'routes-all';

    protected const KEY_BY_PATTERN = 'routes-by-pattern';

    protected const KEY_BY_NAME = 'routes-by-name';

    protected const KEY_BY_ENTITY_ACTION = 'routes-by-entity-action';

    protected const KEY_DISPATCHER_DATA = 'routes-compiled';

    /**
     * @var Route[]
     */
    protected $allRoutes = [];

    /**
     * @var array[]
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
    protected $byEntity = [];

    /**
     * @var ByTypeContainer[]
     */
    protected $byTypeContainers = [];

    /**
     * @var array
     */
    protected $registrars = [];

    /**
     * @var bool
     */
    protected $registrarsCalled = false;

    /**
     * @var array
     */
    protected $compiledData = [];

    public function register(callable $registrar, array $attributes = [])
    {
        if (!$this->registrarsCalled) {
            $this->registrars[] = [
                'registrar'     => $registrar,
                'attributes'    => $attributes
            ];
            return;
        }
        @\trigger_error('Routes were already registered. Try to register all routes before the first access.', E_USER_WARNING);
        $this->callRegistrars([['registrar' => $registrar, 'attributes'=>$attributes]]);
    }

    /**
     * @param Dispatcher $dispatcher
     * @param string $clientType
     * @return void
     */
    public function fillDispatcher(Dispatcher $dispatcher, string $clientType)
    {
        $this->callRegistrarsOnce();
        if (isset($this->compiledData[self::KEY_DISPATCHER_DATA][$clientType])) {
            $dispatcher->fill($this->compiledData[self::KEY_DISPATCHER_DATA][$clientType]);
            return;
        }
        if (!isset($this->byClientType[$clientType])) {
            return;
        }
        foreach ($this->byClientType[$clientType] as $routeArray) {
            foreach ($routeArray['methods'] as $method) {
                $dispatcher->add($method, $routeArray['pattern'], $routeArray);
            }
        }
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
        $this->callRegistrarsOnce();
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
        $this->callRegistrarsOnce();

        // Small simplification when passing console method
        if ($method === Input::CONSOLE && func_num_args() === 2) {
            $clientType = Input::CLIENT_CONSOLE;
        }

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
        $this->callRegistrarsOnce();
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
        $this->callRegistrarsOnce();
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
        $this->callRegistrarsOnce();
        return array_keys($this->byPattern);
    }

    /**
     * @return int
     */
    public function count() : int
    {
        return count(iterator_to_array($this->getIterator()));
    }

    /**
     * Compile all route data into array.
     *
     * @param RouterContract $router
     * @return array
     */
    public function compile(RouterContract $router) : array
    {
        $this->callRegistrarsOnce();

        $compiled = [];

        $compiled[self::KEY_ALL] = $this->allRoutes;
        $compiled[self::KEY_BY_PATTERN] = $this->byPattern;
        $compiled[self::KEY_BY_NAME] = $this->byName;
        $compiled[self::KEY_BY_ENTITY_ACTION] = $this->byEntity;
        $compiled[self::KEY_DISPATCHER_DATA] = [];

        foreach ($this->clientTypes() as $clientType) {
            $dispatcher = $router->getDispatcher($clientType);
            $this->fillDispatcher($dispatcher, $clientType);
            $compiled[self::KEY_DISPATCHER_DATA][$clientType] = $dispatcher->toArray();
        }

        $compiled[self::KEY_VALID] = true;

        return $compiled;
    }

    /**
     * Get the previously assigned compiled data.
     *
     * @return array
     */
    public function getCompiledData() : array
    {
        return $this->compiledData;
    }

    /**
     * Set the previously by compile() generated array.
     *
     * @param $compiledData
     * @return $this
     */
    public function setCompiledData(&$compiledData) : RouteRegistry
    {
        $this->compiledData = &$compiledData;
        $this->allRoutes = $compiledData[self::KEY_ALL];
        $this->byPattern = $compiledData[self::KEY_BY_PATTERN];
        $this->byName = $compiledData[self::KEY_BY_NAME];
        $this->byEntity = $compiledData[self::KEY_BY_ENTITY_ACTION];
        return $this;
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

            if (!isset($this->byClientType[$clientType])) {
                $this->byClientType[$clientType] = [];
            }
            $this->byClientType[$clientType][] = $data;

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
     * Create the collector, which is usually the object registering your routes
     *
     * @param array $attributes (optional)
     *
     * @return RouteCollector
     */
    protected function newCollector($attributes=[]) : RouteCollector
    {
        return new RouteCollector($attributes);
    }

    protected function callRegistrarsOnce()
    {
        if ($this->registrarsCalled || isset($this->compiledData[self::KEY_VALID])) {
            return;
        }

        $this->registrarsCalled = true;

        $this->callRegistrars($this->registrars);

    }

    protected function callRegistrars(array $registrars)
    {
        foreach ($registrars as $info) {
            $collector = $this->newCollector($info['attributes']);
            $routes = $info['registrar']($collector);

            if (!$collector->isEmpty()) {
                $this->addRoutes($collector);
                continue;
            }
            if (is_iterable($routes)) {
                $this->addRoutes($routes);
            }
        }
    }

    /**
     * @param Route[] $routes
     * @return void
     */
    protected function addRoutes($routes)
    {
        foreach($routes as $route) {
            $this->addRoute($route);
        }
    }
}
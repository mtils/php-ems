<?php
/**
 *  * Created by mtils on 14.12.19 at 07:06.
 **/

namespace Ems\Routing;

use ArrayAccess;
use ArrayIterator;
use Ems\Contracts\Routing\Command;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Response;
use Exception;
use OutOfBoundsException;
use Traversable;

use function get_class;
use function in_array;
use function is_array;
use function is_object;

/**
 * Class CompilableRouter
 *
 * The compilable router allows to compile all dispatcher data to use a performance
 * optimized route storage.
 * Put anything with ArrayAccess into setStorage($storage) and it will be used
 * as its compiled storage.
 * With EMS Cache like this: setStorage(Cache::until('+1 day'))
 *
 * @package Ems\Routing
 */
class CompilableRouter implements RouterContract
{

    public const KEY_VALID = 'routes-cached';

    protected const KEY_ALL = 'routes-all';

    protected const KEY_BY_PATTERN = 'routes-by-pattern';

    protected const KEY_BY_NAME = 'routes-by-name';

    protected const KEY_BY_ENTITY_ACTION = 'routes-by-entity-action';

    protected const KEY_DISPATCHER_DATA = 'routes-compiled';

    /**
     * @var RouterContract
     */
    protected $router;

    /**
     * @var ArrayAccess|array
     */
    protected $compiledData = [];

    /**
     * @var Dispatcher[]
     */
    protected $optimizedDispatchers = [];

    /**
     * @var callable[]
     */
    protected $loadListeners = [];

    /**
     * @var bool
     */
    protected $listenersCalled = false;

    /**
     * @param RouterContract $router
     */
    public function __construct(RouterContract $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritDoc}
     *
     * Reimplemented to skip every call when compiled data is present
     *
     * @param callable $registrar
     * @param array $attributes
     */
    public function register(callable $registrar, array $attributes = [])
    {
        // Bravely trusting the hopefully assigned compiled data
    }

    /**
     * Use the router as a normal middleware.
     *
     * @param Input $input
     * @param callable $next
     *
     * @return Response
     */
    public function __invoke(Input $input, callable $next)
    {
        return $next($this->route($input));
    }

    /**
     * {@inheritDoc}
     * Reimplemented to "inject" the compiled data into the dispatcher before
     * the source router uses it.
     *
     * @param Input $routable
     */
    public function route(Input $routable) : Input
    {
        $this->callListenersOnce();
        // Trigger loading of data
        $this->getDispatcher($routable->getClientType());
        return $this->router->route($routable);
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     *
     * @throws Exception
     */
    public function getIterator()
    {
        $this->callListenersOnce();
        $routes = [];
        foreach ($this->compiledData[self::KEY_ALL] as $routeData) {
            $routes[] = $this->toRoute($routeData);
        }
        return new ArrayIterator($routes);
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
        $this->callListenersOnce();
        if (!isset($this->compiledData[self::KEY_BY_PATTERN][$clientType][$pattern])) {
            return [];
        }

        $result = [];

        /** @var Route $route */
        foreach ($this->compiledData[self::KEY_BY_PATTERN][$clientType][$pattern] as $routeData) {
            if (!$method || in_array($method, $routeData['methods'])) {
                $result[] = $this->toRoute($routeData);
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
        $this->callListenersOnce();
        if (isset($this->compiledData[self::KEY_BY_NAME][$clientType][$name])) {
            return $this->toRoute($this->compiledData[self::KEY_BY_NAME][$clientType][$name]);
        }
        throw new KeyNotFoundException("Route named '$name' not found for clientType '$clientType'.");
    }

    /**
     * @param $entity
     * @param string $action
     * @param string $clientType
     * @return Route
     */
    public function getByEntityAction($entity, string $action = 'index', string $clientType = Input::CLIENT_WEB): Route
    {
        $this->callListenersOnce();
        $key = is_object($entity) ? get_class($entity) : "$entity";
        if (isset($this->compiledData[self::KEY_BY_ENTITY_ACTION][$clientType][$key][$action])) {
            return $this->toRoute($this->compiledData[self::KEY_BY_ENTITY_ACTION][$clientType][$key][$action]);
        }
        throw new OutOfBoundsException("Action '$action' not found for entity '$key'");
    }


    /**
     * Return all known unique client types (by route registrations)
     *
     * @return string[]
     */
    public function clientTypes() : array
    {
        return array_keys($this->compiledData[self::KEY_DISPATCHER_DATA]);
    }

    /**
     * {@inheritDoc}
     * Reimplemented to fill compiled data into the dispatcher on first load.
     *
     * @param string $clientType
     *
     * @return Dispatcher
     */
    public function getDispatcher(string $clientType) : Dispatcher
    {

        if (isset($this->optimizedDispatchers[$clientType])) {
            return $this->optimizedDispatchers[$clientType];
        }

        $dispatcher = $this->router->getDispatcher($clientType);

        if (!isset($this->compiledData[self::KEY_DISPATCHER_DATA][$clientType])) {
            return $dispatcher;
        }

        $dispatcher->fill($this->compiledData[self::KEY_DISPATCHER_DATA][$clientType]);

        $this->optimizedDispatchers[$clientType] = $dispatcher;

        return $dispatcher;

    }

    /**
     * @return array|ArrayAccess
     */
    public function getCompiledData()
    {
        return $this->compiledData;
    }

    /**
     * Set the storage. Use anything with array access (Ems\Core\Storage, Ems\Cache\Cache, ...);
     *
     * @param array|ArrayAccess $compiledData
     *
     * @return $this
     */
    public function setCompiledData(&$compiledData) : CompilableRouter
    {
        $this->compiledData = &$compiledData;
        $this->optimizedDispatchers = [];
        return $this;
    }

    /**
     * Create the compiled data and return it.
     */
    public function compile() : array
    {

        $all = [];
        $byName = [];
        $byPattern = [];
        $byEntityAction = [];

        $compiled = [];

        /** @var Route $route */
        foreach ($this->router as $route) {

            $routeData = $route->toArray();

            $all[] = $routeData;
            $pattern = $route->pattern;


            foreach ($route->clientTypes as $clientType) {

                if (!isset($byPattern[$clientType])) {
                    $byPattern[$clientType] = [];
                }

                if (!isset($byName[$clientType])) {
                    $byName[$clientType] = [];
                }

                if (!isset($byPattern[$clientType][$pattern])) {
                    $byPattern[$clientType][$pattern] = [];
                }

                $byPattern[$clientType][$pattern][] = $routeData;

                if ($name = $route->name) {
                    $byName[$clientType][$name] = $routeData;
                }

                if (!$entity = $route->entity) {
                    continue;
                }
                if (!isset($byEntityAction[$clientType])) {
                    $byEntityAction[$clientType] = [];
                }
                if (!isset($byEntityAction[$clientType][$entity])) {
                    $byEntityAction[$clientType][$entity] = [];
                }
                $byEntityAction[$clientType][$entity][$route->action] = $routeData;
            }

        }

        $compiled[self::KEY_ALL] = $all;
        $compiled[self::KEY_BY_PATTERN] = $byPattern;
        $compiled[self::KEY_BY_NAME] = $byName;
        $compiled[self::KEY_BY_ENTITY_ACTION] = $byEntityAction;
        $compiled[self::KEY_DISPATCHER_DATA] = [];

        foreach ($this->router->clientTypes() as $clientType) {
            $compiled[self::KEY_DISPATCHER_DATA][$clientType] = $this->router->getDispatcher($clientType)->toArray();
        }

        $compiled[self::KEY_VALID] = true;

        return $compiled;
    }

    /**
     * @param array $properties
     * @return Route
     */
    protected function toRoute(array $properties) : Route
    {
        if (isset($properties['command']) && is_array($properties['command'])) {
            $properties['command'] = Command::fromArray($properties['command']);
        }
        return Route::fromArray($properties);
    }

    protected function callListenersOnce()
    {
        if ($this->listenersCalled) {
            return;
        }
        $this->listenersCalled = true;
        foreach ($this->loadListeners as $listener) {
            $listener($this);
        }
    }
}
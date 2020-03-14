<?php
/**
 *  * Created by mtils on 14.12.19 at 07:06.
 **/

namespace Ems\Routing;

use ArrayAccess;
use ArrayIterator;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\Exceptions\KeyNotFoundException;
use Exception;
use Traversable;
use function in_array;

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

    const KEY_VALID = 'routes-cached';

    const KEY_ALL = 'routes-all';

    const KEY_BY_PATTERN = 'routes-by-pattern';

    const KEY_BY_NAME = 'routes-by-name';

    const KEY_DISPATCHER_DATA = 'routes-compiled';

    /**
     * @var RouterContract
     */
    protected $router;

    /**
     * @var ArrayAccess|array
     */
    protected $storage = [];

    /**
     * @var Dispatcher[]
     */
    protected $optimizedDispatchers = [];

    public function __construct(RouterContract $router, $storage=[])
    {
        $this->router = $router;
        $this->storage = $storage;
    }

    /**
     * {@inheritDoc}
     *
     * Reimplemented to skip every call in compiled mode
     *
     * @param callable $registrar
     * @param array $attributes
     */
    public function register(callable $registrar, array $attributes = [])
    {
        // Skip every register call in "compiled" mode
        if ($this->isCompiled()) {
            return;
        }
        $this->router->register($registrar, $attributes);
    }

    /**
     * {@inheritDoc}
     * Reimplemented to "inject" the compiled data into the dispatcher before
     * the source router uses it.
     *
     * @param Routable $routable
     */
    public function route(Routable $routable)
    {

        $clientType = $routable->clientType();

        // If we now this dispatcher it was already optimized
        if (isset($this->optimizedDispatchers[$clientType]) || !$this->isCompiled()) {
            return $this->router->route($routable);
        }

        $dispatcher = $this->router->getDispatcher($clientType);

        $dispatcher->fill($this->storage[self::KEY_DISPATCHER_DATA][$clientType]);

        $this->optimizedDispatchers[$clientType] = $dispatcher;

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
        if (isset($this->storage[self::KEY_ALL])) {
            return new ArrayIterator($this->storage[self::KEY_ALL]);
        }
        return $this->router->getIterator();
    }

    /**
     * {@inheritDoc}
     *
     * @param string $pattern
     * @param string $method (optional)
     *
     * @return Route[]
     */
    public function getByPattern($pattern, $method = null)
    {
        if (!$this->isCompiled()) {
            return $this->router->getByPattern($pattern, $method);
        }

        if (!isset($this->storage[self::KEY_BY_PATTERN][$pattern])) {
            return [];
        }

        if (!$method) {
            return $this->storage[self::KEY_BY_PATTERN][$pattern];
        }

        $result = [];

        /** @var Route $route */
        foreach ($this->storage[self::KEY_BY_PATTERN][$pattern] as $route) {
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
        if (!$this->isCompiled()) {
            return $this->router->getByName($name);
        }

        if (isset($this->storage[self::KEY_BY_NAME][$name])) {
            return $this->storage[self::KEY_BY_NAME][$name];
        }

        throw new KeyNotFoundException("Route named '$name' not found.");
    }

    /**
     * Return all known unique client types (by route registrations)
     *
     * @return string[]
     */
    public function clientTypes()
    {
        if (!$this->isCompiled()) {
            return $this->router->clientTypes();
        }
        return array_keys($this->storage[self::KEY_DISPATCHER_DATA]);
    }

    /**
     * {@inheritDoc}
     * Reimplemented to fill compiled data into the dispatcher on first load.
     *
     * @param string $clientType
     *
     * @return Dispatcher
     */
    public function getDispatcher($clientType)
    {

        if (isset($this->optimizedDispatchers[$clientType])) {
            return $this->optimizedDispatchers[$clientType];
        }

        $dispatcher = $this->router->getDispatcher($clientType);

        if (!isset($this->storage[self::KEY_DISPATCHER_DATA][$clientType])) {
            return $dispatcher;
        }

        $dispatcher->fill($this->storage[self::KEY_DISPATCHER_DATA][$clientType]);

        $this->optimizedDispatchers[$clientType] = $dispatcher;

        return $dispatcher;

    }

    /**
     * Create the compiled routes in cache.
     */
    public function compile()
    {

        $all = [];
        $byName = [];
        $byPattern = [];

        /** @var Route $route */
        foreach ($this->router as $route) {

            $all[] = $route;
            $pattern = $route->pattern;

            if (!isset($byPattern[$pattern])) {
                $byPattern[$pattern] = [];
            }

            $byPattern[$pattern][] = $route;

            if ($name = $route->name) {
                $byName[$name] = $route;
            }
        }

        $this->storage[self::KEY_ALL] = $all;
        $this->storage[self::KEY_BY_PATTERN] = $byPattern;
        $this->storage[self::KEY_BY_NAME] = $byName;

        $dispatcherData = [];

        foreach ($this->router->clientTypes() as $clientType) {
            $dispatcherData[$clientType] = $this->router->getDispatcher($clientType)->toArray();
        }

        $this->storage[self::KEY_DISPATCHER_DATA] = $dispatcherData;

        $this->storage[self::KEY_VALID] = true;
    }

    /**
     * Clean all compiled data (like make clean in qmake/Qt)
     */
    public function clean()
    {
        foreach ([self::KEY_ALL, self::KEY_BY_PATTERN, self::KEY_BY_NAME,
                  self::KEY_DISPATCHER_DATA, self::KEY_VALID] as $key) {
            unset($this->storage[$key]);
        }
        $this->optimizedDispatchers = [];
    }

    /**
     * Check if the router works with compiled data.
     *
     * @return bool
     */
    public function isCompiled()
    {
        return isset($this->storage[self::KEY_VALID]);
    }

    /**
     * Set the storage. Use anything with array access (Ems\Core\Storage, Ems\Cache\Cache, ...);
     *
     * @param array|ArrayAccess $storage
     *
     * @return $this
     */
    public function setStorage(&$storage)
    {
        $this->storage = $storage;
        return $this;
    }

}
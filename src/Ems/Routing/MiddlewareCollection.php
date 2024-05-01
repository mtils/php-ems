<?php
/**
 *  * Created by mtils on 20.07.19 at 06:57.
 **/

namespace Ems\Routing;

use ArrayIterator;
use Closure;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Ems\Contracts\Routing\MiddlewarePlacer;
use Ems\Core\Collections\StringList;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Lambda;
use Ems\Core\Support\CustomFactorySupport;
use ReflectionException;
use Traversable;

use function array_filter;
use function array_merge;
use function array_slice;
use function func_get_args;
use function is_array;
use function is_string;

class MiddlewareCollection implements MiddlewareCollectionContract
{
    use CustomFactorySupport;

    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var array
     */
    protected $before = [];

    /**
     * @var array
     */
    protected $after = [];

    /**
     * @var array
     */
    protected static $aliases = [];

    /**
     * MiddlewareCollection constructor.
     *
     * @param callable|null $instanceResolver (optional)
     */
    public function __construct(callable $instanceResolver=null)
    {
        $this->_customFactory = $instanceResolver;
    }

    /**
     * {@inheritDoc}
     *
     * @param Input $input
     *
     * @return \Ems\Core\Response
     *
     * @throws ReflectionException
     */
    public function __invoke(Input $input)
    {
        $runner = $this->makeRunner($this->buildKeys());
        return $runner($input);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name
     *
     * @return callable
     *
     * @throws ReflectionException
     */
    public function middleware($name)
    {
        return $this->getOrCreateMiddleware($this->middlewares[$name]);
    }


    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @param callable|string $middleware
     * @param string|array $parameters (optional)
     *
     * @return MiddlewarePlacer
     */
    public function add($name, $middleware, $parameters = null)
    {
        // Cleanup previous position/parameters if necessary
        if (isset($this->middlewares[$name])) {
            $this->offsetUnset($name);
        }

        $this->middlewares[$name] = $middleware;

        $parameters = is_array($parameters) ? $parameters : array_slice(func_get_args(),2);
        $this->parameters[$name] = $parameters;

        $handle = [
            'name'          => $name,
            'scopes'        => [],
            'clientTypes'   => [],
            'middleware'     => $middleware
        ];

        $placer = new MiddlewarePlacer(
            $handle,
            $this->beforeAdder(),
            $this->afterAdder(),
            $this->invoker(),
            $this->replacer($name)
        );

        return $placer;

    }

    /**
     * {@inheritDoc}
     *
     * @param string $name
     *
     * @return array
     */
    public function parameters($name)
    {
        $this->failOnMissingName($name);
        return $this->parameters[$name];
    }

    /**
     * {@inheritDoc}
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->middlewares[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        $this->failOnMissingName($offset);
        return $this->middlewares[$offset];
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->add($offset, $value);
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        $this->failOnMissingName($offset);

        unset($this->middlewares[$offset]);

        if (isset($this->parameters[$offset])) {
            unset($this->parameters[$offset]);
        }

        $filter = function ($name) use ($offset) {
            return $name != $offset;
        };

        foreach ($this->before as $beforeThis=>$runThat) {
            $this->before[$beforeThis] = array_filter($runThat, $filter);
        }

        foreach ($this->after as $afterThis=>$runThat) {
            $this->after[$afterThis] = array_filter($runThat, $filter);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param array $keys (optional)
     *
     * @return self
     **/
    public function clear(array $keys = null)
    {
        if ($keys === null) {
            $this->middlewares = [];
            $this->parameters = [];
            $this->before = [];
            $this->after = [];
            return $this;
        }

        if (!$keys) {
            return $this;
        }

        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }


    /**
     * {@inheritDoc}
     *
     * @return StringList
     **/
    public function keys()
    {
        return new StringList($this->buildKeys());
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     **/
    public function toArray()
    {
        $array = [];
        foreach ($this->buildKeys() as $key) {
            if (isset($this->middlewares[$key])) {
                $array[$key] = $this->middlewares[$key];
            }
        }
        return $array;
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * Count elements of an object
     * @link https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->middlewares);
    }

    /**
     * Map a middleware name to a class. So you can just do:
     *
     * Middleware::alias('auth', IsAuthenticatedMiddleware::class)
     * and then do:
     * $routes->get('profile/edit')->middleware('auth')
     *
     * $container->alias('auth', IsAuthenticatedMiddleware::class) would have
     * the same effect but it looks strange to make a global binding to name
     * middlewares.
     *
     * @param string $name
     * @param string $class
     * @return void
     */
    public static function alias(string $name, string $class)
    {
        self::$aliases[$name] = $class;
    }

    /**
     * Get the class of alias (or an empty string if there is none)
     *
     * @param string $alias
     * @return string
     */
    public static function getClassOfAlias(string $alias) : string
    {
        return self::$aliases[$alias] ?? '';
    }

    /**
     * Find the alias of $class or return an empty string.
     *
     * @param string $class
     * @return string
     */
    public static function getAliasOfClass(string $class) :  string
    {
        foreach (self::$aliases as $alias=>$mappedClass) {
            if ($mappedClass === $class) {
                return $alias;
            }
        }
        return '';
    }

    /**
     * Remove a previously added alias
     * @param string $alias
     * @return void
     */
    public static function removeAlias(string $alias)
    {
        if (isset(self::$aliases[$alias])) {
            unset(self::$aliases[$alias]);
        }
    }

    /**
     * Create the object that walks over the middlewares.
     *
     * @param array $keys
     *
     * @return callable
     *
     * @throws ReflectionException
     */
    protected function makeRunner(array $keys)
    {
        /** @var MiddlewareRunner $runner */
        $runner = $this->createObject(MiddlewareRunner::class, [$this, $keys]);
        return $runner;
    }

    /**
     * Build the keys in the desired order.
     *
     * @return array
     */
    protected function buildKeys()
    {
        $added = [];
        $keys = [];

        foreach ($this->middlewares as $key=>$value) {

            if (isset($added[$key])) {
                continue;
            }

            if (isset($this->before[$key])) {
                foreach ($this->before[$key] as $before) {
                    if (!isset($added[$before])) {
                        $keys[] = $before;
                        $added[$before] = true;
                    }
                }
            }

            // Perhaps it was added by a before (x before x??)
            if (!isset($added[$key])) {
                $keys[] = $key;
                $added[$key] = true;
            }

            if (!isset($this->after[$key])) {
                continue;

            }

            foreach ($this->after[$key] as $after) {
                if (!isset($added[$after])) {
                    $keys[] = $after;
                    $added[$after] = true;
                }
            }

        }

        return $keys;
    }

    /**
     * @return Closure
     */
    protected function beforeAdder() : Closure
    {
        return function ($addThis, $before) {
            $this->addBefore($before, $addThis);
        };
    }

    /**
     * @return Closure
     */
    protected function afterAdder() : Closure
    {
        return function ($addThis, $before) {
            $this->addAfter($before, $addThis);
        };
    }

    /**
     * @return Closure
     */
    protected function invoker() : Closure
    {
        return function ($middleware, $input, callable $next, ...$args) {
            $middleware = $this->getOrCreateMiddleware($middleware);
            return Lambda::callFast($middleware, array_merge([$input, $next], $args));
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function replacer(string $name) : Closure
    {
        return function (MiddlewarePlacer $placer) use ($name) {
            if (!$this->middlewares[$name] instanceof MiddlewarePlacer) {
                $this->middlewares[$name] = $placer;
            }
        };
    }

    /**
     * This is used by the Positioner.
     *
     * @param string $beforeThis
     * @param array $addThat
     */
    protected function addBefore(string $beforeThis, array $addThat)
    {
        if (!isset($this->before[$beforeThis])) {
            $this->before[$beforeThis] = [];
        }
        $this->before[$beforeThis][] = $addThat['name'];
    }

    /**
     * This is used by the Positioner.
     *
     * @param string $afterThis
     * @param array $addThat
     */
    protected function addAfter(string $afterThis, array $addThat)
    {
        if (!isset($this->after[$afterThis])) {
            $this->after[$afterThis] = [];
        }
        $this->after[$afterThis][] = $addThat['name'];
    }

    /**
     * @param string $name
     */
    protected function failOnMissingName(string $name)
    {
        if (!isset($this->middlewares[$name])) {
            throw new KeyNotFoundException("Middleware '$name' does not exist.");
        }
    }

    /**
     * @param string|callable $middleware
     *
     * @return callable
     *
     * @throws ReflectionException
     */
    protected function getOrCreateMiddleware($middleware)
    {
        if (!is_string($middleware)) {
            return $middleware;
        }
        return $this->createObject(self::$aliases[$middleware] ?? $middleware);
    }
}
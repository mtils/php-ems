<?php
/**
 *  * Created by mtils on 20.07.19 at 06:57.
 **/

namespace Ems\Routing;

use ArrayIterator;
use Closure;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\Positioner;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Ems\Core\Collections\StringList;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Support\CustomFactorySupport;
use Traversable;
use function array_filter;
use function array_slice;
use function func_get_args;
use function is_array;
use function is_string;
use ReflectionException;

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
     * @var Closure
     */
    protected $beforeAdder;

    /**
     * @var Closure
     */
    protected $afterAdder;

    /**
     * MiddlewareCollection constructor.
     *
     * @param callable $instanceResolver (optional)
     */
    public function __construct(callable $instanceResolver=null)
    {
        $this->_customFactory = $instanceResolver;

        $this->beforeAdder = function ($addThis, $before) {
            $this->addBefore($before, $addThis);
        };
        $this->afterAdder = function ($addThis, $after) {
            $this->addAfter($after, $addThis);
        };
    }

    /**
     * {@inheritDoc}
     *
     * @param Input $input
     *
     * @return Response
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
        $middleware = $this->middlewares[$name];
        if (!is_string($middleware)) {
            return $middleware;
        }
        /** @var callable $middleware */
        $middleware = $this->createObject($middleware);
        return $middleware;
    }


    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @param callable|string $middleware
     * @param string|array $parameters (optional)
     *
     * @return Positioner
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

        return new Positioner($name, $this->beforeAdder, $this->afterAdder);

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
    public function count()
    {
        return count($this->middlewares);
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
        /** @var callable $runner */
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
     * This is used by the Positioner.
     *
     * @param string $beforeThis
     * @param string $addThat
     */
    protected function addBefore($beforeThis, $addThat)
    {
        if (!isset($this->before[$beforeThis])) {
            $this->before[$beforeThis] = [];
        }
        $this->before[$beforeThis][] = $addThat;
    }

    /**
     * This is used by the Positioner.
     *
     * @param string $afterThis
     * @param string $addThat
     */
    protected function addAfter($afterThis, $addThat)
    {
        if (!isset($this->after[$afterThis])) {
            $this->after[$afterThis] = [];
        }
        $this->after[$afterThis][] = $addThat;
    }

    /**
     * @param string $name
     */
    protected function failOnMissingName($name)
    {
        if (!isset($this->middlewares[$name])) {
            throw new KeyNotFoundException("Middleware '$name' does not exist.");
        }
    }
}
<?php
/**
 *  * Created by mtils on 30.08.20 at 08:49.
 **/

namespace Ems\Contracts\Core\Containers;


use ArrayAccess;

use ArrayIterator;
use Countable;
use IteratorAggregate;

use function is_subclass_of;

/**
 * Class ByTypeContainer
 *
 * This container is a utility to store handlers or any data by type inheritance.
 * So in a case you need "$this is the handler for objects of this class or this
 * interface" this could be the right utility for this use case.
 *
 * @package Ems\Contracts\Core\Containers
 */
class ByTypeContainer implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * @var array
     */
    protected $extensions = [];

    /**
     * @var array
     */
    protected $forInstanceOfCache = [];

    /**
     * Find the handler/data for $class. Check by inheritance.
     *
     * @param string $class
     *
     * @return mixed
     */
    public function forInstanceOf(string $class)
    {
        if (isset($this->forInstanceOfCache[$class])) {
            return $this->forInstanceOfCache[$class];
        }
        if (isset($this->extensions[$class])) {
            $this->forInstanceOfCache[$class] = $this->extensions[$class];
            return $this->extensions[$class];
        }
        foreach ($this->extensions as $abstract=>$extension) {
            if (is_subclass_of($class, $abstract)) {
                $this->forInstanceOfCache[$class] = $extension;
                return $extension;
            }
        }
        return null;
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->extensions[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->extensions[$offset];
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
     */
    public function offsetSet($offset, $value)
    {
        $this->extensions[$offset] = $value;
        $this->forInstanceOfCache = [];
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->extensions[$offset]);
        $this->forInstanceOfCache = [];
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->extensions);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->extensions);
    }
}
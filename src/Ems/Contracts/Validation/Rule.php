<?php


namespace Ems\Contracts\Validation;

use Countable;
use IteratorAggregate;
use Ems\Contracts\Core\Stringable;

/**
 * A Rule represents a costraint description for one
 * value. So instead of just parsing strings this makes manipulations
 * much easier. (e.g. $definition->min = 3 or $definition->between = [2,8])
 * 
 **/
interface Rule extends Countable, IteratorAggregate, Stringable
{
    /**
     * Fill the Rule with new definitions. Pass a string
     * like "required|unique|min:2" or an array like
     * ['required', 'unique', 'min:2']
     *
     * @param array|string $definition
     *
     * @return self
     **/
    public function fill($definition);

    /**
     * Merge the Rule with new definitions. Existing keys
     * will not cleared.
     *
     * A new instance is always returned if you call merge() !!
     *
     * @param array|string $definition
     *
     * @return self
     **/
    public function merge($definition);

    /**
     * Clear all definitions
     *
     * @return self
     **/
    public function clear();

    /**
     * Return the parameters for constraint named $name. If only one parameter
     * was passed return the one parameter else the array of parameters.
     *
     * @param string $name
     *
     * @return mixed
     **/
    public function __get($name);

    /**
     * Add an constraint or set the parameter of an existing constraint. If you
     * dont pass an array it will be automatically converted to one.
     *
     * @param string $name
     * @param mixed  $parameter
     *
     * @return void
     **/
    public function __set($name, $parameter);

    /**
     * Return true if a constraint named $name was added
     *
     * @param string $name
     *
     * @return bool
     **/
    public function __isset($name);

    /**
     * Unset the constraint with name $name
     *
     * @param string $name
     *
     * @return void
     **/
    public function __unset($name);

}

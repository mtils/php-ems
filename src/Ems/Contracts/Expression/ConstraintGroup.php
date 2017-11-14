<?php

namespace Ems\Contracts\Expression;


use ArrayAccess;


/**
 * A ConstraintGroup is a conjunction of constraints.
 * It holds only one instance of constraint per name.
 *
 * ArrayAccess is used to access the constraints by their ->name().
 **/
interface ConstraintGroup extends LogicalGroup, ArrayAccess
{
    /**
     * Return all constraints indexed by its names.
     *
     * @return array
     **/
    public function constraints();

    /**
     * Fill the ConstraintGroup with new definitions. Pass a string
     * like "required|unique|min:2" or an array like
     * ['required', 'unique', 'min:2']
     *
     * @param array|string $definition
     *
     * @return self
     **/
    public function fill($definition);

    /**
     * Merge the ConstrainGroup with new definitions. Existing keys
     * will not cleared.
     *
     * @param array|string $definition
     *
     * @return self
     **/
    public function merge($definition);

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

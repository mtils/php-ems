<?php


namespace Ems\Contracts\Core;


/**
 * A named callable chain is a set of callables which has been added to
 * this object with a name.
 *
 **/
interface NamedCallableChain
{

    /**
     * Returns the default chain e.g. ['no_tokens','no_method']
     *
     * @return array
     **/
    public function getChain();

    /**
     * Set the default chain. You can pass a |-separated string or an array.
     * Returns itself, not a new instance
     *
     * @param string|array $chain
     * @return self (same instance)
     **/
    public function setChain($chain);

    /**
     * Return a new instance with the passed castername. Pass multiple arguments or
     * an array to pass multiple callable names. The callable names will be merged
     * with the chain() casters. If you add a leading ! the passed caster will
     * be ommited (removed from chain before the new instance is created)
     *
     * @param string|array $callable
     * @return self (New instance)
     **/
    public function with($callable);

    /**
     * Add a callable with name $name
     *
     * @param string $name
     * @param callable $callable
     * @return self (same instance)
     **/
    public function extend($name, callable $callable);

}

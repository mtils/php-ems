<?php


namespace Ems\Contracts\Core;


/**
 * This interface is for all objects which support a custom object
 * factory.
 * So you can assign your own callable to create objects. The class
 * or interface name is first argument to the callable. The second
 * are parameters, but you should avoid using parameters.
 * The Ems\Core\IOCContainer is by default callable so use this if
 * you use it...
 **/
interface SupportsCustomFactory
{
    /**
     * Assign a factory to the class implementing this interface
     *
     * @param callable $factory
     *
     * @return self
     **/
    public function createObjectsBy(callable $factory);
}

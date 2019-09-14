<?php

namespace Ems\Core\Patterns;

use OutOfBoundsException;
use function call_user_func;

/**
 * The Extendable trait allows to extend objects.
 *
 * For example:
 * $myObject = new MyObject;
 * $myObject->extend('myFunc', function($foo){});
 * $myObject->callExtension('myFunc', ['fooContent']);
 *
 * You can implement a __call method and defer the call to callExtension.
 *
 * For example:
 * $myObject = new MyObject;
 * $myObject->extend('myFunc', function($foo){});
 * $myObject->myFunc('fooContent');
 **/
trait Extendable
{
    /**
     * Here the callables are held.
     *
     * @var array
     **/
    protected $_extensions = [];

    /**
     * The callables stored here are always called, not matter of the $name.
     *
     * @var array
     **/
    protected $_alwaysCalledExtensions = [];

    /**
     * Add an "extension". A callable (array, closure,..) which You can call by
     * YouClass->$name().
     *
     * @param string   $name
     * @param callable $callable
     *
     * @return self
     **/
    public function extend($name, callable $callable)
    {
        $this->_extensions[$name] = $callable;

        return $this;
    }

    /**
     * Return if an extension with name $name exists.
     *
     * @param string $name
     *
     * @return bool
     **/
    public function hasExtension($name)
    {
        return isset($this->_extensions[$name]);
    }

    /**
     * Call the extension named $name with $params.
     *
     * @param string $name
     * @param array  $params (optional)
     *
     * @return mixed
     **/
    public function callExtension($name, array $params = [])
    {
        $extension = $this->getExtension($name);

        $result = call_user_func($extension, ...$params);

        $this->callListeners($name, $result);

        return $result;
    }

    /**
     * Return the extension named $name.
     *
     * @param string $name
     *
     * @return mixed
     **/
    public function getExtension($name)
    {
        if ($this->hasExtension($name)) {
            return $this->_extensions[$name];
        }

        throw new OutOfBoundsException(get_class($this).": No extension named \"$name\" found");
    }

    /**
     * Call this callable everytime an extension get called.
     * The callable will be called with the following params:
     * callable($name, $result)
     * It will be called after the extension was called.
     *
     * @param callable $callable
     *
     * @return self
     **/
    public function alwaysCall(callable $callable)
    {
        $this->_alwaysCalledExtensions[] = $callable;

        return $this;
    }

    /**
     * Calls the listeners, which get informed on every call.
     *
     * @param string $name
     * @param mixed  $result
     **/
    protected function callListeners($name, &$result)
    {
        foreach ($this->_alwaysCalledExtensions as $listener) {
            call_user_func($listener, $name, $result);
        }
    }
}

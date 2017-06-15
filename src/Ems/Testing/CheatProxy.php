<?php

namespace Ems\Testing;

use InvalidArgumentException;

/**
 * The CheatProxy allows a nice syntax when cheating an object
 * Just create new proxy and use its properties and methods to fake
 * its source.
 **/
class CheatProxy
{

    /**
     * @var object
     **/
    protected $source;

    /**
     * @param object $source
     **/
    public function __construct($source)
    {
        if (!is_object($source)) {
            throw new InvalidArgumentException('CheatProxy only allows cheating objects');
        }
        $this->source = $source;
    }

    /**
     * Get the value of a property (even if it is protected or private)
     *
     * @param string $key
     *
     * @return mixed
     **/
    public function __get($key)
    {
        return Cheat::get($this->source, $key);
    }

    /**
     * Set the value of a property (even if it is protected or private)
     *
     * @param string $key
     *
     * @return mixed
     **/
    public function __set($key, $value)
    {
        return Cheat::set($this->source, $key, $value);
    }

    /**
     * Call a method on source (even if it is protected or private)
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed
     **/
    public function __call($method, array $args=[])
    {
        return Cheat::call($this->source, $method, $args);
    }

}

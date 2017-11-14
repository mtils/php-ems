<?php

namespace Ems\Testing;

use ReflectionProperty;
use ReflectionMethod;

/**
 * This class allows to get protected and private values from classes
 * and allows to call private and protected methods.
 * So you can test your classes without adding artificial public methods
 * just to test them.
 **/
class Cheat
{
    /**
     * Get the value of a property, even if it is protected or private.
     *
     * @param object $object
     * @param string $property
     *
     * @return mixed
     **/
    public static function get($object, $property)
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    /**
     * Set the value of a property, even if it is protected or private.
     *
     * @param object $object
     * @param string $property
     * @param mixed  $value
     *
     * @return void
     **/
    public static function set($object, $property, $value)
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        $reflection->setValue($object, $value);
    }

    /**
     * Call a method of an object, even if it is protected or private.
     *
     * @param object $object
     * @param array  $args   (optional)
     *
     * @return mixed
     **/
    public static function call($object, $method, array $args = [])
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    /**
     * Nice shortcut for self::call(). Call Cheat::a($parser)->foo or
     * Cheat::a($parser)->foo($args) to have a shorter syntax or use the
     * proxy to call it multiple times.
     *
     * @param object $object
     *
     * @return CheatProxy
     **/
    public static function a($object)
    {
        return new CheatProxy($object);
    }

    /**
     * Alias for self::a() to allow more correct english wordings:
     * Cheat::a($parser)->parseInternal() vs. Cheat::an($object)->parse()
     *
     * @param object $object
     *
     * @return CheatProxy
     **/
    public static function an($object)
    {
        return static::a($object);
    }
}

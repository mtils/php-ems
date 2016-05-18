<?php


namespace Ems\Testing;

use ReflectionProperty;
use ReflectionMethod;


/**
 * This class allows to get protected and private values from classes
 * and allows to call private and protected methods.
 * So you can test your classes without adding artificial public methods
 * just to test them
 **/
class Cheat
{

    /**
     * Get the value of a property, even if it is protected or private
     *
     * @param object $object
     * @param string $property
     * @return mixed
     **/
    public static function get($object, $property)
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }

    /**
     * Call a method of an object, even if it is protected or private
     *
     * @param object $object
     * @param array $args (optional)
     * @return mixed
     **/
    public static function call($object, $method, array $args=[])
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($object, $args);
    }

}
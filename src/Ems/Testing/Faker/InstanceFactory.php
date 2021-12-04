<?php
/**
 *  * Created by mtils on 04.12.2021 at 16:22.
 **/

namespace Ems\Testing\Faker;

abstract class InstanceFactory
{
    /**
     * Create test data for $class.
     *
     * @param string $class
     * @param Generator $faker
     * @return array
     */
    abstract public function data(string $class, Generator $faker) : array;

    /**
     * Create an instance of $class
     *
     * @param string $class
     * @param Generator $faker
     * @return object
     */
    public function instance(string $class, Generator $faker)
    {
        $object = new $class;
        foreach ($this->data($class, $faker) as $key=>$value) {
            $object->$key = $value;
        }
        return $object;
    }
}
<?php
/**
 *  * Created by mtils on 15.09.19 at 10:18.
 **/

namespace Ems\Contracts\Routing;


use Ems\Contracts\Core\Arrayable;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty as RP;

/**
 * Class ConsoleParameter
 *
 * This is the base class for console arguments and options
 *
 * @package Ems\Contracts\Routing
 */
abstract class ConsoleParameter implements Arrayable
{
    /**
     * The name. Just used internally to access the argument
     * @example $input->argument('name')
     * @example $input->option('name')
     *
     * @var string
     */
    public $name = '';

    /**
     * Is this parameter required?
     *
     * @var bool
     */
    public $required = false;

    /**
     * Type of this parameter. Can be bool|string|array
     *
     * @var string
     */
    public $type = 'bool';

    /**
     * The default value.
     *
     * @var mixed
     */
    public $default;

    /**
     * The description of the parameter.
     *
     * @var string
     */
    public $description = '';

    /**
     * @var string[]
     */
    protected static $propertyNames;

    /**
     * @param array $data
     *
     * @return $this
     *
     * @throws ReflectionException
     */
    public function fill(array $data)
    {
        foreach (static::propertyNames() as $key) {

            if (isset($data[$key])) {
                $this->$key = $data[$key];
            }
        }
        return $this;
    }

    /**
     * @return array
     *
     * @throws ReflectionException
     */
    public function toArray()
    {
        $array = [];
        foreach (static::propertyNames() as $key) {
            $array[$key] = $this->$key;
        }
        return $array;
    }

    /**
     * @return string[]
     *
     * @throws ReflectionException
     */
    protected static function propertyNames()
    {
        if (static::$propertyNames !== null) {
            return static::$propertyNames;
        }

        static::$propertyNames = [];

        $reflection = new ReflectionClass(static::class);
        foreach ($reflection->getProperties(RP::IS_PUBLIC) as $property) {
            if ($property->isDefault() && !$property->isStatic()) {
                static::$propertyNames[] = $property->getName();
            }
        }
        return static::$propertyNames;
    }
}
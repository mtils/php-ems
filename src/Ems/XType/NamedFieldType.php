<?php

namespace Ems\XType;

use ArrayAccess;
use IteratorAggregate;
use Countable;
use ArrayIterator;
use OutOfBoundsException;
use Ems\Contracts\XType\XType;

abstract class NamedFieldType extends AbstractType implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var array
     **/
    public $default = [];

    /**
     * The key types are stored in this array.
     *
     * @var array
     **/
    protected $namedTypes = [];

    /**
     * Return all type keys/properties/names.
     *
     * @return array
     **/
    public function names()
    {
        return array_keys($this->namedTypes);
    }

    /**
     * Return the type of $name.
     *
     * @param string $name
     *
     * @return \Ems\Contracts\XType\XType
     **/
    public function offsetGet($name)
    {
        return $this->namedTypes[$name];
    }

    /**
     * Set the type of $name.
     *
     * @param string                     $name
     * @param \Ems\Contracts\XType\XType $type
     **/
    public function offsetSet($name, $type)
    {
        $this->set($name, $type);
    }

    /**
     * Check if $name exists.
     *
     * @param string $name
     *
     * @return bool
     **/
    public function offsetExists($name)
    {
        return isset($this->namedTypes[$name]);
    }

    /**
     * Delete $name.
     *
     * @param string $name
     **/
    public function offsetUnset($name)
    {
        unset($this->namedTypes[$name]);
    }

    /**
     * Get the type of $name or throw an exception.
     *
     * @param string $name
     *
     * @return \Ems\Contracts\XType\XType
     *
     * @throws \OutOfBoundsException
     **/
    public function get($name)
    {
        if (!$this->offsetExists($name)) {
            throw new OutOfBoundsException("Key '$name' not found");
        }

        return $this->offsetGet($name);
    }

    /**
     * Set a value (for fluid syntax).
     *
     * @param string                     $name
     * @param \Ems\Contracts\XType\XType $type
     *
     * @return self
     **/
    public function set($name, XType $type)
    {
        $this->namedTypes[$name] = $type;

        return $this;
    }

    /**
     * @return int
     **/
    public function count()
    {
        return count($this->namedTypes);
    }

    /**
     * @return \ArrayIterator
     **/
    public function getIterator()
    {
        return new ArrayIterator($this->namedTypes);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return self
     *
     * @throws \Ems\Contracts\Core\Unsupported
     **/
    public function fill(array $attributes = [])
    {
        $filtered = [];

        foreach ($attributes as $name => $value) {
            if (isset(static::$propertyMap[static::class][$name]) ||
                isset(static::$aliases[static::class][$name])) {
                $filtered[$name] = $value;
                continue;
            }

            $this->set($name, $value);
        }

        return parent::fill($filtered);
    }
}

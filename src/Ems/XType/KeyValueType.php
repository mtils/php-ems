<?php

namespace Ems\XType;

use ArrayAccess;
use IteratorAggregate;
use Countable;
use ArrayIterator;
use OutOfBoundsException;
use Ems\Contracts\XType\XType;
use Ems\Core\Exceptions\UnsupportedParameterException;

abstract class KeyValueType extends AbstractType implements ArrayAccess, Countable, IteratorAggregate
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
     * @var callable
     **/
    protected $keyProvider;

    /**
     * @var bool
     **/
    protected $keysLoaded = false;

    /**
     * Return all type keys/properties/names.
     *
     * @return array
     **/
    public function names()
    {
        $this->loadKeysIfNotLoaded();
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
        $this->loadKeysIfNotLoaded();
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
        $this->loadKeysIfNotLoaded();
        return isset($this->namedTypes[$name]);
    }

    /**
     * Delete $name.
     *
     * @param string $name
     **/
    public function offsetUnset($name)
    {
        $this->loadKeysIfNotLoaded();
        unset($this->namedTypes[$name]);
    }

    /**
     * Get the type of $name or throw an exception.
     *
     * @param string $name
     *
     * @throws \OutOfBoundsException
     *
     * @return \Ems\Contracts\XType\XType
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
        $this->loadKeysIfNotLoaded();
        $this->namedTypes[$name] = $type;

        return $this;
    }

    /**
     * @return int
     **/
    public function count()
    {
        $this->loadKeysIfNotLoaded();
        return count($this->namedTypes);
    }

    /**
     * @return \ArrayIterator
     **/
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $this->loadKeysIfNotLoaded();
        return new ArrayIterator($this->namedTypes);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @throws \Ems\Contracts\Core\Unsupported
     *
     * @return self
     **/
    public function fill(array $attributes = [])
    {
        $filtered = [];

        foreach ($attributes as $name => $value) {

            // This is done by the parent
            if (in_array($name, ['name', 'constraints'])) {
                continue;
            }

            if (isset(static::$propertyMap[static::class][$name]) ||
                isset(static::$aliases[static::class][$name])) {
                $filtered[$name] = $value;
                continue;
            }

            if (!$value instanceof XType) {
                throw new UnsupportedParameterException("The value of $name has to be instanceof XType");
            }

            $this->set($name, $value);
        }

        return parent::fill($filtered);
    }

    /**
     * Return the xtype properties as an array.
     *
     * @return array
     **/
    public function toArray()
    {
//         $this->loadKeysIfNotLoaded();
        return parent::toArray();
    }

    /**
     * To allow a deferred loading of the keys assign a callable which provides
     * the keys once an access to the keys is performed.
     * Use this to not loading your complete ORM/Models if the first model is
     * loaded.
     *
     * @param callable $keyProvider
     *
     * @return self
     **/
    public function provideKeysBy(callable $keyProvider)
    {
        $this->keyProvider = $keyProvider;
        return $this;
    }

    /**
     * Return true if this KeyValueType has a key provider
     *
     * @return bool
     **/
    public function hasKeyProvider()
    {
        return (bool)$this->keyProvider;
    }

    /**
     * Loads the keys if not done before
     *
     * @see self::provideKeysBy()
     */
    protected function loadKeysIfNotLoaded()
    {
        if (!$this->keyProvider || $this->keysLoaded) {
            return;
        }

        $this->keysLoaded = true;

        foreach (call_user_func($this->keyProvider) as $key=>$xType) {
            $this->namedTypes[$key] = $xType;
        }
    }
}

<?php

namespace Ems\Core\Support;

use Ems\Core\Collections\OrderedList;

/**
 * This trait is an implementation without any extra functionality. Use it
 * for fast passthru array access.
 **/
trait FastArrayDataTrait
{
    /**
     * @var array
     **/
    protected $_attributes = [];

    /**
     * Check if $offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     **/
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->_attributes[$offset]);
    }

    /**
     * Get value of $offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     **/
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->_attributes[$offset];
    }

    /**
     * Set the value of $offset.
     *
     * @param mixed $offset
     * @param mixed $value
     **/
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->_attributes[$offset] = $value;
    }

    /**
     * Unset $offset.
     *
     * @param mixed $offset
     **/
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->_attributes[$offset]);
    }

    /**
     * Return an array of key (strings)
     *
     * @return OrderedList
     *
     * @see \Ems\Contracts\Core\HasKeys
     **/
    public function keys()
    {
        return new OrderedList(array_keys($this->_attributes));
    }

    /**
     * Turn the object into an array (only root will be converted)
     *
     * @return array
     *
     * @see \Ems\Contracts\Core\Arrayable
     **/
    public function toArray()
    {
        return $this->_attributes;
    }

    /**
     * Clears the internal array
     *
     * @param array $keys (optional)
     *
     * @return self
     **/
    public function clear(array $keys=null)
    {
        if ($keys === null) {
            $this->_attributes = [];
            return $this;
        }

        if (!$keys) {
            return $this;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->_attributes)) {
                $this->offsetUnset($key);
            }
        }

        return $this;
    }
}

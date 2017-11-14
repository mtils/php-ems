<?php

namespace Ems\Core\Support;

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
    public function offsetSet($offset, $value)
    {
        $this->_attributes[$offset] = $value;
    }

    /**
     * Unset $offset.
     *
     * @param mixed $offset
     **/
    public function offsetUnset($offset)
    {
        unset($this->_attributes[$offset]);
    }

    /**
     * Return an array of key (strings)
     *
     * @return array
     *
     * @see \Ems\Contracts\Core\HasKeys
     **/
    public function keys()
    {
        return array_keys($this->_attributes);
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

}

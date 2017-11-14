<?php

namespace Ems\Core\Support;

use Ems\Core\Collections\StringList;

/**
 * This trait is ArrayData with just a boot method
 **/
trait BootingArrayData
{
    /**
     * @var array
     **/
    protected $_attributes = [];

    /**
     * @var bool
     **/
    protected $_booted = false;

    /**
     * Check if $offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     **/
    public function offsetExists($offset)
    {
        $this->bootOnce();
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
        $this->bootOnce();
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
        $this->bootOnce();
        $this->_attributes[$offset] = $value;
    }

    /**
     * Unset $offset.
     *
     * @param mixed $offset
     **/
    public function offsetUnset($offset)
    {
        $this->bootOnce();
        unset($this->_attributes[$offset]);
    }

    /**
     * Return an array of key (strings)
     *
     * @return StringList
     *
     * @see \Ems\Contracts\Core\HasKeys
     **/
    public function keys()
    {
        $this->bootOnce();
        return new StringList(array_keys($this->_attributes));
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
        $this->bootOnce();
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

    /**
     * Fill the attributes with $attributes
     *
     * @param array $attributes
     * @param bool  $isFromStorage (default=true)
     *
     * @return self
     **/
    protected function fillAttributes(array $attributes, $isFromStorage=true)
    {
        $this->_attributes = $attributes;
        return $this;
    }

    /**
     * Provide the attributes to autofill them
     **/
    protected function autoAssignAttributes()
    {
        $attributes = isset($this->defaultAttributes) ? $this->defaultAttributes : [];
        // Default attributes are not counted as "from storage"
        $this->fillAttributes($attributes, false);
    }

    /**
     * Boot the trait
     **/
    protected function bootOnce()
    {
        if ($this->_booted) {
            return;
        }

        $this->fillOnce();

        $this->_booted = true;
    }

    /**
     * Fill the attributes once.
     **/
    protected function fillOnce()
    {
        $this->autoAssignAttributes();
    }

}

<?php


namespace Ems\Core\Support;


trait ArrayAccessMethods
{
    /**
     * @var array
     **/
    protected $_attributes = [];

    /**
     * @var bool
     **/
    protected $_autoFilled = false;

    /**
     * Check if $offset exists
     *
     * @param mixed $offset
     * @return bool
     **/
    public function offsetExists($offset)
    {
        $this->fillOnce();
        return isset($this->_attributes[$offset]);
    }

    /**
     * Get value of $offset
     *
     * @param mixed $offset
     * @return mixed
     **/
    public function offsetGet($offset)
    {
        $this->fillOnce();
        return $this->_attributes[$offset];
    }

    /**
     * Set the value of $offset
     *
     * @param mixed $offset
     * @param mixed $value
     * @return null
     **/
    public function offsetSet($offset, $value)
    {
        $this->_attributes[$offset] = $value;
    }

    /**
     * Unset $offset
     *
     * @param mixed $offset
     * @return null
     **/
    public function offsetUnset($offset)
    {
        $this->fillOnce();
        unset($this->_attributes[$offset]);
    }

    protected function fillOnce()
    {
        if ($this->_autoFilled) {
            return;
        }

        if (method_exists($this, 'fillAttributes')) {
            $this->fillAttributes();
        }

        $this->_autoFilled = true;
    }
}

<?php


namespace Ems\Core;

use Ems\Core\Helper;

class QueryableArrayWithState extends ArrayWithState
{

    /**
     * {@inheritdoc}
     *
     * @param mixed $offset
     *
     * @return bool
     **/
    public function offsetExists($offset)
    {
        $this->bootOnce();
        return Helper::offsetExists($this->_attributes, $offset);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $offset
     *
     * @return mixed
     **/
    public function offsetGet($offset)
    {
        $this->bootOnce();
        return Helper::offsetGet($this->_attributes, $offset);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $offset
     * @param mixed $value
     **/
    public function offsetSet($offset, $value)
    {
        $this->bootOnce();
        Helper::offsetSet($this->_attributes, $offset, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $offset
     **/
    public function offsetUnset($offset)
    {
        $this->bootOnce();
        Helper::offsetUnset($this->_attributes, $offset);
    }

}

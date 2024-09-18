<?php


namespace Ems\Core;



class QueryableArrayWithState extends ArrayWithState
{

    /**
     * {@inheritdoc}
     *
     * @param mixed $offset
     *
     * @return bool
     **/
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->bootOnce();
        Helper::offsetUnset($this->_attributes, $offset);
    }

}

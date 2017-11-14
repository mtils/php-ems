<?php

namespace Ems\Model;

use Ems\Contracts\Model\Result;
use ArrayIterator;
use Iterator;
use Traversable;
use UnexpectedValueException;

/**
 * @see \Ems\Contracts\Model\Result
 **/
trait ResultTrait
{
    /**
     * @var object
     **/
    protected $_creator;

    /**
     * {@inheritdoc}
     *
     * @return object
     **/
    public function creator()
    {
        return $this->_creator;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     **/
    public function first()
    {
        foreach ($this->getIterator() as $result) {
            return $result;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     **/
    public function last()
    {
        $result = null;

        foreach ($this->getIterator() as $result) {
            // Empty working code ...
        }

        return $result;
    }
}

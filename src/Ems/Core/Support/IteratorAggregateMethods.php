<?php

namespace Ems\Core\Support;

use ArrayIterator;

trait IteratorAggregateMethods
{
    /**
     * Return the count of this array like object.
     *
     * @return int
     **/
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->_attributes);
    }
}

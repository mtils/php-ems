<?php

namespace Ems\Core\Support;

trait CountableMethods
{
    /**
     * Return the count of this array like object.
     *
     * @return int
     **/
    public function count()
    {
        return count($this->_attributes);
    }
}

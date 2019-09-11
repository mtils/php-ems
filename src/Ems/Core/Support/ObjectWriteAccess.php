<?php
/**
 *  * Created by mtils on 11.09.19 at 13:03.
 **/

namespace Ems\Core\Support;


use Ems\Core\Exceptions\KeyNotFoundException;
use function get_class;
use function isFalse;

trait ObjectWriteAccess
{
    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if ($this->canWriteInUnknownProperty() && !isset($this->_properties[$name])) {
            throw new KeyNotFoundException("Property $name not found in " . get_class($this));
        }
        $this->_properties[$name] = $value;
    }

    /**
     * @return bool
     */
    protected function canWriteInUnknownProperty()
    {
        return false;
    }
}
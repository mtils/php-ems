<?php
/**
 *  * Created by mtils on 29.06.19 at 07:00.
 **/

namespace Ems\Core\Support;

use function array_key_exists;
use Ems\Contracts\Core\ObjectAccess;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\NotImplementedException;
use function get_class;

/**
 * Trait ObjectReadAccess
 *
 * @see ObjectAccess
 *
 * @package Ems\Core\Support
 * @property $_properties protected
 * */
trait ObjectReadAccess
{
    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->_properties[$name])) {
            return $this->_properties[$name];
        }

        if (!isset($this->_properties)) {
            throw new NotImplementedException('Add a _properties array to ' . get_class($this));
        }

        if (!array_key_exists($name, $this->_properties) && $this->shouldFailOnMissingProperty()) {
            throw new KeyNotFoundException("Property $name not found in " . get_class($this));
        }

        return null;
    }

    /**
     * is triggered by calling isset() or empty() on inaccessible members.
     *
     * @param $name string
     * @return bool
     *
     * @link https://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->_properties);
    }

    /**
     * @return bool
     */
    protected function shouldFailOnMissingProperty()
    {
        return true;
    }
}
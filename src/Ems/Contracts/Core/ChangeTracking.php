<?php


namespace Ems\Contracts\Core;

use ArrayAccess;

/**
 * This is a container for any data. Return all known or
 * possible keys this container holds.
 **/
interface ChangeTracking
{
    /**
     * Return if the object is new (and not loaded from
     * storage)
     *
     * @return bool
     **/
    public function isNew();

    /**
     * Return if the object was modified ($keys=null) or
     * the key $keys was modified or
     * if ANY of the passed $keys (array) where modified.
     * Modified means different then in storage.
     *
     * @param string|array $keys (optional)
     *
     * @return bool
     **/
    public function wasModified($keys=null);

    /**
     * Return if the $keys were loaded from storage.
     * Pass a string for one key, pass an array to check
     * if ALL of the keys were loaded, pass nothing to
     * check if the object was loaded at all (!isNew).
     * This method does not check if the key was modified.
     *
     * @param string|array $keys (optional)
     *
     * @return bool
     **/
    public function wasLoaded($keys=null);

    /**
     * Return the "original" value of $key. If the key exists in the
     * original array, return the value if it is null. If the key does not
     * exist return $default.
     *
     * @param string|null $key (optional)
     * @param mixed       $default (optional)
     *
     * @return mixed
     **/
    public function getOriginal($key = null, $default = null);

    /**
     * Reset the state to the original.
     *
     * @return self
     **/
    public function reset();

}

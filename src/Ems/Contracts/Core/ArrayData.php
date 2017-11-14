<?php


namespace Ems\Contracts\Core;

use ArrayAccess;

/**
 * This is a container for any data.
 **/
interface ArrayData extends HasKeys, ArrayAccess, Arrayable
{
    /**
     * Clears the internal data. Pass an array of keys to only
     * clear this keys.
     * Only clear all keys if $keys === null to avoid unintented
     * deletions by passing an empty array.
     * So self::clear([]) must do nothing.
     *
     * @param array $keys (optional)
     *
     * @return self
     **/
    public function clear(array $keys=null);
}

<?php

namespace Ems\Contracts\Assets;

use Countable;
use ArrayAccess;
use IteratorAggregate;
use Ems\Contracts\Core\PathFinder;

/**
 * The asset Registry is the place to go to import you assets.
 *
 * Countable:
 *
 * @method     Registrar count() Return to amount of all assigned assets
 *
 * ArrayAccess:
 * @method     bool offsetExists($offset) Check if $group $offset exists
 * @method     mixed offsetGet($offset) Return all files of group $offset
 * @method     void offsetSet($offset, $value) Throws an BadMethodCallException
 * @method     void offsetUnset($offset) Throws an BadMethodCallException
 *
 * IteratorAggregate:
 * @method     Registrar getIterator() Return an iterator over $group=>$files
 **/
interface Registry extends Registrar, PathFinder, Countable, ArrayAccess, IteratorAggregate
{
    /**
     * Return all groups which where assigned by import().
     *
     * @return array
     **/
    public function groups();
}

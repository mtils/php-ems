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
 * @method     Registrar offsetExists($offset) Check if $group $offset exists
 * @method     Registrar offsetGet($offset) Return all files of group $offset
 * @method     Registrar offsetSet($offset) Throws an BadMethodCallException
 * @method     Registrar offsetUnset($offset) Throws an BadMethodCallException
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

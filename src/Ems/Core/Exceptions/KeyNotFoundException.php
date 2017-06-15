<?php

namespace Ems\Core\Exceptions;

use OutOfBoundsException;
use Ems\Contracts\Core\Errors\NotFound;

/**
 * Throw a KeyNotFoundException if a key does not exist inside an container,
 * array, collection, ...
 **/
class KeyNotFoundException extends OutOfBoundsException implements NotFound
{
}

<?php


namespace Ems\Core\Exceptions;

use OutOfBoundsException;
use Ems\Contracts\Core\Errors\NotFound;

/**
 * Throw a ResourceNotFoundException if a resource like a database entry
 * or a file or a session wasnt found
 **/
class ResourceNotFoundException extends OutOfBoundsException implements NotFound{}

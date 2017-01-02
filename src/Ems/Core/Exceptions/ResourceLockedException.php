<?php

namespace Ems\Core\Exceptions;

use RuntimeException;
use Ems\Contracts\Core\Errors\ConcurrentAccess;

/**
 * Throw a ResourceNotFoundException if a resource like a database entry
 * or a file or a session wasnt found.
 **/
class ResourceLockedException extends RuntimeException implements ConcurrentAccess
{
}

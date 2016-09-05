<?php


namespace Ems\Core;

use RuntimeException;
use Ems\Contracts\Core\NotFound;

/**
 * Throw a ResourceNotFoundException if a resource like a database entry
 * or a file or a session wasnt found
 **/
class ResourceNotFoundException extends RuntimeException implements NotFound{}

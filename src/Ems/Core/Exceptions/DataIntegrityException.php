<?php

namespace Ems\Core\Exceptions;

use RuntimeException;
use Ems\Contracts\Core\Errors\DataCorruption;

/**
 * Throw a DataIntegrityException if an integrity check failed or otherwise
 * corrupted data was found.
 **/
class DataIntegrityException extends RuntimeException implements DataCorruption
{
}

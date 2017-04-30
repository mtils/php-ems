<?php

namespace Ems\Core\Exceptions;

use RuntimeException;
use Ems\Contracts\Core\Errors\ConfigurationError;

/**
 * Throw a MisConfiguredException if an object was not configured propely
 **/
class MisConfiguredException extends RuntimeException implements ConfigurationError
{
}

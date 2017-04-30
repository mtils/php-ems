<?php

namespace Ems\Core\Exceptions;

use RuntimeException;
use Ems\Contracts\Core\Errors\ConfigurationError;

/**
 * Throw a UnConfiguredException if an object was not configured
 **/
class UnConfiguredException extends RuntimeException implements ConfigurationError
{
}

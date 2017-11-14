<?php

namespace Ems\Core\Exceptions;

use OutOfBoundsException;
use Ems\Contracts\Core\Errors\SyntaxError;

/**
 * Throw a MissingArgumentException if under some cirumstances you need an optional
 * parameter.
 **/
class MissingArgumentException extends OutOfBoundsException implements SyntaxError
{
}

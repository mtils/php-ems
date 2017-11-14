<?php

namespace Ems\Core\Exceptions;

use DomainException;
use Ems\Contracts\Core\Errors\ConstraintFailure;

/**
 * Throw a ConstraintException if some constraint would be violatet without
 * throwing the exception.
 **/
class ConstraintViolationException extends DomainException implements ConstraintFailure
{
}

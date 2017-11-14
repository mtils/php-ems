<?php

namespace Ems\Core\Exceptions;

use Ems\Contracts\Core\Errors\UnSupported;
use OutOfBoundsException;

class UnsupportedUsageException extends OutOfBoundsException implements UnSupported
{
}

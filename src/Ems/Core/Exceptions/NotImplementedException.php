<?php

namespace Ems\Core\Exceptions;

use Ems\Contracts\Core\Errors\UnSupported;
use OutOfBoundsException;

class NotImplementedException extends OutOfBoundsException implements UnSupported
{
}

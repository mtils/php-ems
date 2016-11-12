<?php

namespace Ems\Cache\Exception;

use Ems\Contracts\Core\Errors\NotFound;
use OutOfBoundsException;

class CacheMissException extends OutOfBoundsException implements NotFound
{
}

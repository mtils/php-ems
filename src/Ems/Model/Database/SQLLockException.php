<?php

namespace Ems\Model\Database;

use Ems\Contracts\Core\Errors\ConcurrentAccess;
use Ems\Contracts\Model\Database\SQLException;

class SQLLockException extends SQLException implements ConcurrentAccess
{
    //
}

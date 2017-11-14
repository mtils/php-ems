<?php

namespace Ems\Model\Database;

use Ems\Contracts\Core\Errors\MemoryExceededError;
use Ems\Contracts\Model\Database\SQLException;

class SQLExceededException extends SQLException implements MemoryExceededError
{
    //
}

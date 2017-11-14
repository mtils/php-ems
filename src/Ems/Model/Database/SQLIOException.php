<?php

namespace Ems\Model\Database;

use Ems\Contracts\Model\Database\SQLException;
use Ems\Contracts\Core\Errors\DataCorruption;

class SQLIOException extends SQLException implements DataCorruption
{
    //
}

<?php

namespace Ems\Model\Database;

use Ems\Contracts\Core\Errors\SyntaxError;
use Ems\Contracts\Model\Database\SQLException;

class SQLSyntaxException extends SQLException implements SyntaxError
{
    //
}

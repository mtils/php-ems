<?php

namespace Ems\Model\Database;

use Ems\Contracts\Model\Database\SQLException;
use Ems\Contracts\Core\Errors\AccessDenied;

class SQLDeniedException extends SQLException implements AccessDenied
{
    //
}

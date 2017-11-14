<?php

namespace Ems\Model\Database;

use Ems\Contracts\Core\Errors\ConstraintFailure;
use Ems\Contracts\Model\Database\SQLException;

class SQLConstraintException extends SQLException implements ConstraintFailure
{
    //
}

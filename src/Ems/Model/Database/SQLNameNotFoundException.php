<?php

namespace Ems\Model\Database;

use Ems\Contracts\Core\Errors\NotFound;
use Ems\Contracts\Model\Database\SQLException;

/**
 * A SQLNameNotFoundException is used to mark an exception as caused by
 * a missing database, table or a column:
 **/
class SQLNameNotFoundException extends SQLException implements NotFound
{
    /**
     * Here you can mark if database|table|column|view was not found.
     *
     * @var string
     **/
    public $missingType = '';
}

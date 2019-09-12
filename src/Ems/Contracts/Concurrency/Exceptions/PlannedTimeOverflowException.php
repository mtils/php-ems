<?php
/**
 *  * Created by mtils on 08.09.19 at 13:24.
 **/

namespace Ems\Contracts\Concurrency\Exceptions;


use Ems\Contracts\Concurrency\ConcurrencyError;
use OverflowException;

/**
 * Class PlannedTimeOverflowException
 *
 * This exception will be thrown when you plan a lock to expire at a distinct
 * time and a process exceeds this time.
 *
 * @package Ems\Contracts\Concurrency\Exceptions
 */
class PlannedTimeOverflowException extends OverflowException implements ConcurrencyError
{
    //
}
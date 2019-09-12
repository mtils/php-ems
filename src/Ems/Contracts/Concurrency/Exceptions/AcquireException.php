<?php
/**
 *  * Created by mtils on 08.09.19 at 13:24.
 **/

namespace Ems\Contracts\Concurrency\Exceptions;


use Ems\Contracts\Concurrency\ConcurrencyError;
use OverflowException;
use RuntimeException;

/**
 * Class AcquireException
 *
 * This exception will be thrown if it is impossible to get a lock. This
 * would happen if the disk is full, the server distributing the locks is
 * offline, denies to create or something else.
 * Is is NOT thrown if the lock cannot be acquired because another process
 * locked it.
 * This exception means "It makes no sense to try this again"
 *
 * @package Ems\Contracts\Concurrency\Exceptions
 */
class AcquireException extends RuntimeException implements ConcurrencyError
{
    //
}
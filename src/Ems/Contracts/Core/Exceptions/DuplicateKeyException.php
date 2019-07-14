<?php
/**
 *  * Created by mtils on 06.02.18 at 05:19.
 **/

namespace Ems\Contracts\Core\Exceptions;

use Ems\Contracts\Core\Errors\ConstraintFailure;
use RuntimeException;

/**
 * Class DuplicateKeyException
 *
 * This exception is thrown if you try to assign two unique things the same key
 *
 * @package Ems\Contracts\Core\Exceptions
 */
class DuplicateKeyException extends RuntimeException implements ConstraintFailure
{
    //
}
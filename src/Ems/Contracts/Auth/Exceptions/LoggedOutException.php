<?php
/**
 *  * Created by mtils on 14.08.2022 at 08:05.
 **/

namespace Ems\Contracts\Auth\Exceptions;

use RuntimeException;

/**
 * This exception is thrown when an action can not be performed because auth
 * means the current user is Auth::NOBODY.
 * In HTTP status said it is an 401
 */
class LoggedOutException extends RuntimeException
{
    //
}
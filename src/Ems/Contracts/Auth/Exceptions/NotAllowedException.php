<?php
/**
 *  * Created by mtils on 17.08.2022 at 07:05.
 **/

namespace Ems\Contracts\Auth\Exceptions;

use RuntimeException;

/**
 * This exception can be thrown if Auth::allowed() returned false when
 * checking the access.
 * In HTTP status this would be an 403 Forbidden.
 */
class NotAllowedException extends RuntimeException
{
    //
}
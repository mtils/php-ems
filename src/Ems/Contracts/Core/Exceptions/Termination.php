<?php
/**
 *  * Created by mtils on 11.08.19 at 13:49.
 **/

namespace Ems\Contracts\Core\Exceptions;

use RuntimeException;

/**
 * Class Termination
 *
 * Use this class to intentionally abort a process/handling/iteration without
 * further exception handling.
 * This is a tool exception and is not for errors.
 *
 * @package Ems\Contracts\Core\Exceptions
 */
class Termination extends RuntimeException
{
    //
}
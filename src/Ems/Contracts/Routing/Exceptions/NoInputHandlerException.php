<?php
/**
 *  * Created by mtils on 17.08.19 at 12:31.
 **/

namespace Ems\Contracts\Routing\Exceptions;


use Ems\Contracts\Core\Errors\ConfigurationError;
use RuntimeException;

/**
 * Class NoInputHandlerException
 *
 * This exception is thrown if the middleware didnt find an InputHandler
 *
 * @package Ems\Contracts\Routing\Exceptions
 */
class NoInputHandlerException extends RuntimeException implements ConfigurationError
{
    //
}
<?php
/**
 *  * Created by mtils on 17.08.19 at 12:31.
 **/

namespace Ems\Contracts\Routing\Exceptions;


use Ems\Contracts\Core\Errors\ConfigurationError;
use RuntimeException;

/**
 * Class MultipleResponsesWithoutInputHandlerException
 *
 * This exception is thrown if multiple middlewares returned a response. The
 * last response returning middleware has to always be instanceof InputHandler
 *
 * @package Ems\Contracts\Routing\Exceptions
 */
class MultipleResponsesWithoutInputHandlerException extends RuntimeException implements ConfigurationError
{
    //
}
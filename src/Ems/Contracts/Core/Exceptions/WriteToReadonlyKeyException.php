<?php
/**
 *  * Created by mtils on 11.09.19 at 09:08.
 **/

namespace Ems\Contracts\Core\Exceptions;


use DomainException;
use Ems\Contracts\Core\Errors\UnSupported;

/**
 * Class WriteToReadonlyKeyException
 *
 * This Exception is thrown when you try to write into an read only key like a
 * primary id or auto generated timestamps and such
 *
 * @package Ems\Contracts\Core\Exceptions
 */
class WriteToReadonlyKeyException extends DomainException implements UnSupported
{
    //
}
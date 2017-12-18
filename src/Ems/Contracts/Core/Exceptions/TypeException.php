<?php
/**
 *  * Created by mtils on 17.12.17 at 08:55.
 **/

namespace Ems\Contracts\Core\Exceptions;


use Ems\Contracts\Core\Errors\ConstraintFailure;
use InvalidArgumentException;

class TypeException extends InvalidArgumentException implements ConstraintFailure
{
    //
}
<?php
/**
 *  * Created by mtils on 29.05.2022 at 08:43.
 **/

namespace Ems\Contracts\Validation;

use RuntimeException;

/**
 * This exception is thrown if a validator cannot be created.
 */
class ValidatorFabricationException extends RuntimeException
{
    public const NO_FACTORY_FOR_ORM_CLASS = 40400;

    public const UNRESOLVABLE_BY_FACTORY = 50010;

    public const FACTORY_RETURNED_WRONG_TYPE = 50020;

    public const WRONG_TYPE_REGISTERED = 50030;
}
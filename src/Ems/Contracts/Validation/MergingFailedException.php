<?php
/**
 *  * Created by mtils on 20.03.2022 at 21:08.
 **/

namespace Ems\Contracts\Validation;

use RuntimeException;

/**
 * Throw this exception if your validator can not merge rules and mergeRules
 * was called..
 */
class MergingFailedException extends RuntimeException
{
    //
}
<?php

namespace Ems\Contracts\Model;

use Ems\Contracts\Expression\ConditionGroup;

/**
 * A QueryableResult is a result, which can be filtered (before) fetching the
 * results.
 *
 * @example User::where()->where(a, '<>', 'b') // here the second where is from
 * the result.
 **/
interface QueryableResult extends Result, ConditionGroup
{
    //
}

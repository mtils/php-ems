<?php

namespace Ems\Contracts\Expression;

use Ems\Contracts\Core\HasKeys;
use ArrayAccess;


/**
 * A ConditionGroup is a conjunction of conditions.
 * It holds only one instance of constraint per name.
 *
 * ArrayAccess is used to access the conditions by their ->operand() string
 * representation.
 *
 * The keys() method of HasKeys is used to get all operand strings.
 **/
interface ConditionGroup extends LogicalGroup, Conditional, HasKeys
{
    /**
     * Return all conditions. Pass an operand to get all conditions for $operand.
     *
     * @param string|\Ems\Contratcs\Core\Expression $operand (optional)
     *
     * @return array
     **/
    public function conditions($operand=null);

    /**
     * Return true if the ConditionGroup has a condition for $operand or if none
     * passed if it has conditions at all.
     *
     * @param string|\Ems\Contratcs\Core\Expression $operand (optional)
     *
     * @return bool
     **/
    public function hasConditions($operand=null);
}

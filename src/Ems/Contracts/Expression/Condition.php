<?php

namespace Ems\Contracts\Expression;

use Ems\Contracts\Core\Expression as ExpressionContract;


/**
 * A Condition is a combination of an operand and a constraint.
 **/
interface Condition extends ExpressionContract, HasExpressions
{
    /**
     * Return the operand, on which the constraint has to match.
     * Every value which is not an expression is considered as constant and
     * not evaluatable.
     *
     * @return mixed|ExpressionContract
     **/
    public function operand();

    /**
     * Return the constraint on which the operand should match.
     *
     * @return Constraint|ConstraintGroup
     **/
    public function constraint();

}

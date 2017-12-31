<?php

namespace Ems\Contracts\Expression;

use Ems\Contracts\Core\Expression as ExpressionContract;

use Ems\Contracts\Core\Stringable;


/**
 * A constraint represents a constraint definition. It cannot check itself
 * if this definition applies to a value. It is just a unified interface to store
 * how a value must be to match.
 *
 * @example $constraint->name() // equals
 *          $constraint->operator() // =
 *          $constraint->parameters() // [
 *          // would be $key (equals 5) or $key (= 5)
 **/
interface Constraint extends ExpressionContract
{
    /**
     * Return the name of this constraint. (equals, min,...)
     *
     * @return string
     **/
    public function name();

    /**
     * Return an operator (if exists). Some constraints are matching an
     * operator, like equals, min, max. Others not like numeric. If there is
     * no operator, just return an empty string or invent one.
     *
     * @return string
     **/
    public function operator();

    /**
     * Return the parameters for this constraint.
     *
     * @return array
     **/
    public function parameters();
}

<?php

namespace Ems\Contracts\Expression;



/**
 * A Conditional is an interface for simple creation of conditions.
 * All methods have to return new instances on every call.
 **/
interface Queryable
{

    /**
     * The where method creates a and connected group, in which all conditions
     * have to apply to make the whole conditional apply.
     * $operand is auto-converted to an expression if none passed.
     * It is a AND gate (0+0=0|0+1=0|1+0=0|1+1=1)
     *
     * This method has the following signatures:
     *
     * @example where('name', 'Ralf') // name = Ralf
     * @example where('name', 'Ralf')->where('birthday', '2017-01-01') // name = 'Ralf' AND birthday = '2017-01-01'
     * @example where('category_id', 'in', [1,3,4]) // category_id IN (1,3,4)
     * @example where(Expression('CONCAT(firstname,lastname)'), 'like', 'Ralf') // CONCAT(firstname, lastname) LIKE 'Ralf'
     * @example where(new Condition('name', 'equals:Ralf')) // name = 'Ralf'
     * @example where('age', 'min:5|max:18') // age >= 5 AND age <= 18
     * @example where('name', 'Ralf')->where(function (Conditional $conditional) {
     *     $conditional->orWhere('age', '>=', 5)
     *     $conditional->orWhere('age', '<=', 18)
     * }) // name = 'Ralf' AND (age >= 5 OR age <= 18)
     *
     * @param string|\Ems\Constracts\Core\Expression|\Closure $operand
     * @param mixed                                           $operatorOrValue (optional)
     * @param mixed                                           $value
     *
     * @return self
     **/
    public function where($operand, $operatorOrValue=null, $value=null);

}

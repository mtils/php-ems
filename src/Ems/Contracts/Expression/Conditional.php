<?php

namespace Ems\Contracts\Expression;



/**
 * A Conditional is an interface for simple creation of conditions.
 * All methods have to return new instances on every call.
 **/
interface Conditional extends Queryable
{

    /**
     * The orWhere method creates an or connected group, in which any condition
     * has to apply to make the whole conditional apply.
     * It is an OR gate (0+0=0|0+1=1|1+0=1|1+1=1)
     *
     * @param string|\Ems\Constracts\Core\Expression|\Closure $operand
     * @param mixed                                           $operatorOrValue (optional)
     * @param mixed                                           $value
     *
     * @return self
     *
     * @see self::where()
     **/
    public function orWhere($operand, $operatorOrValue=null, $value=null);

    /**
     * The whereNot method creates an nand connected group, in which all of the
     * passed conditions must NOT apply to make the whole conditional apply.
     * It is a NAND gate (0+0=1|0+1=1|1+0=1|1+1=0) or NOT AND !($a && $b)
     *
     * @param string|\Ems\Constracts\Core\Expression|\Closure $operand
     * @param mixed                                           $operatorOrValue (optional)
     * @param mixed                                           $value
     *
     * @return self
     *
     * @see self::where()
     **/
    public function whereNot($operand, $operatorOrValue=null, $value=null);

    /**
     * The whereNone method creates an nand connected group, in which NONE of
     * the passed conditions must apply to make the whole conditional apply.
     * It is a NOR gate (0+0=1|0+1=0|1+0=0|1+1=0) or NOT OR !($a || $b)
     *
     * @param string|\Ems\Constracts\Core\Expression|\Closure $operand
     * @param mixed                                           $operatorOrValue (optional)
     * @param mixed                                           $value
     *
     * @return self
     *
     * @see self::where()
     **/
    public function whereNone($operand, $operatorOrValue=null, $value=null);

}

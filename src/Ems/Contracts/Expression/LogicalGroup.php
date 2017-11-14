<?php

namespace Ems\Contracts\Expression;


use Ems\Contracts\Core\Expression as ExpressionContract;


/**
 * A LogicalGroup is a collection of expressions.
 **/
interface LogicalGroup extends ExpressionContract, HasExpressions
{
    /**
     * Return the operator (AND|OR|NOT|NOR|NAND).
     *
     * @return string (AND|OR|NOT|NOR|NAND)
     **/
    public function operator();

    /**
     * Add an expression.
     *
     * @param ExpressionContract $expression
     *
     * @return self
     **/
    public function add(ExpressionContract $expression);

    /**
     * Remove an expression.
     *
     * @param ExpressionContract $expression
     *
     * @return self
     **/
    public function remove(ExpressionContract $expression);

    /**
     * Remove all expressions.
     *
     * @return self
     **/
    public function clear();

}

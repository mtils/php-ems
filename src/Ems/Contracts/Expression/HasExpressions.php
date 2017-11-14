<?php

namespace Ems\Contracts\Expression;



/**
 * Every expression which holds other expressions should implement this
 * interface to support nested searches for expressions.
 **/
interface HasExpressions
{
    /**
     * Return all expressions
     *
     * @return array
     **/
    public function expressions();

}

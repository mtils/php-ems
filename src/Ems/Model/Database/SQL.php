<?php

namespace Ems\Model\Database;

use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\Result;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Expression;
use Ems\Core\KeyExpression;
use Ems\Core\Patterns\ExtendableTrait;
use Ems\Expression\Condition;
use Ems\Expression\ConditionGroup;
use Ems\Expression\Constraint;
use PDOStatement;

class SQL
{
    /**
     * Try to build a readable sql query of a prepared one.
     *
     * @param string $query
     * @param array  $bindings
     * @param string $quoteChar (default:')
     *
     * @return string
     **/
    public static function render($query, array $bindings=[], $quoteChar="'")
    {

        if (!$bindings) {
            return "$query";
        }

        $keys = [];
        $values = [];

        # build a regular expression for each parameter
        foreach ($bindings as $key=>$value) {
            $keys[] = is_string($key) ? '/:'.$key.'/' : '/[?]/';
            $values[] = is_numeric($value) ? (int)$value : "$quoteChar$value$quoteChar";
        }

        $count=0;

        return preg_replace($keys, $values, "$query", 1, $count);

    }

    /**
     * A shortcut to create a KeyExpression
     *
     * @param string $name
     * @param string $alias (optional)
     *
     * @return KeyExpression
     **/
    public static function key($name, $alias=null)
    {
        return new KeyExpression($name);
    }

// Later...
//     public static function func($name, $parameters)
//     {
//         return 
//     }

    /**
     * Create a new constraint
     *
     * @param string $name
     * @param array $parameters  (optional)
     *
     * @return Constraint
     **/
    public static function rule($operator, $parameters=[], $name=null)
    {
        return new Constraint($name ?: $operator, (array)$parameters, $operator);
    }

    /**
     * Create a new ConditionGroup.
     *
     * @param string|\Ems\Contracts\Core\Expression|\Closure $operand
     * @param mixed                                          $operatorOrValue (optional)
     * @param mixed                                          $value (optional)
     *
     * @return ConditionGroup
     **/
    public static function where($key, $operatorOrValue=null, $value=null)
    {
        $g = new ConditionGroup();

        if (func_num_args() == 1) {
            return $g->where($key);
        }

        if (func_num_args() == 2) {
            return $g->where($key, $operatorOrValue);
        }

        return $g->where($key, $operatorOrValue, $value);

    }

    /**
     * Return a raw expression.
     *
     * @param string
     *
     * @return Expression
     **/
    public static function raw($string)
    {
        return new Expression($string);
    }

}

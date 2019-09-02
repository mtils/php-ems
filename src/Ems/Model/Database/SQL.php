<?php

namespace Ems\Model\Database;

use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Expression;
use Ems\Core\KeyExpression;
use Ems\Core\Patterns\StaticExtendable;
use Ems\Expression\ConditionGroup;
use Ems\Expression\Constraint;
use Ems\Model\Database\Dialects\MySQLDialect;
use Ems\Model\Database\Dialects\SQLiteDialect;
use function array_map;
use function func_get_args;
use function implode;

class SQL
{
    use StaticExtendable;

    const MY = 'mysql';

    const POSTGRES = 'postgresql';

    const SQLITE = 'sqlite';

    const MS = 'mssql';

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

    /**
     * Create a sql dialect out of an string or just return the dialect if it
     * is already a dialect and we dont have a extension that overwrites it.
     *
     * @param string|Stringable $dialect
     *
     * @return Dialect
     */
    public static function dialect($dialect)
    {
        $dialectName = "$dialect";

        // Event if this would be a dialect already check for an extension
        if (static::hasExtension($dialectName)) {
            return static::callExtension($dialectName, [$dialect]);
        }

        if ($dialect instanceof Dialect) {
            return $dialect;
        }

        if ($dialectName == static::SQLITE) {
            return new SQLiteDialect();
        }

        if ($dialectName == static::MY) {
            return new MySQLDialect();
        }

        throw new NotImplementedException("SQL dialect $dialectName is not supported");
    }

    /**
     * Create a string to render an insert statement.
     *
     * @param Dialect $dialect
     * @param array   $values
     *
     * @return string
     */
    public static function renderColumnsForInsert(Dialect $dialect, array $values)
    {
        $columns = [];
        $quotedValues = [];

        foreach ($values as $column=>$value) {
            $columns[] = $dialect->quote($column, Dialect::NAME);
            $quotedValues[] = $dialect->quote($value);
        }

        return '(' . implode(",", $columns) . ")\nVALUES (" . implode(",", $quotedValues) . ')';
    }

    /**
     * Create a string to render an update (without bindings)
     *
     * @param Dialect $dialect
     * @param array   $values
     *
     * @return string
     */
    public static function renderColumnsForUpdate(Dialect $dialect, array $values)
    {
        return static::renderKeyValue($dialect, $values, ",\n");
    }

    /**
     * Make an assoc array to a where string.
     *
     * @param Dialect $dialect
     * @param array   $values
     * @param string  $boolean (default: AND)
     *
     * @return string
     */
    public static function renderColumnsForWhere(Dialect $dialect, array $values, $boolean='AND')
    {
        return static::renderKeyValue($dialect, $values, " $boolean\n");
    }

    /**
     * Render columns in a `$key` = "$value", `$key` = "$value" form.
     *
     * @param Dialect $dialect
     * @param array   $values
     * @param string  $connectBy (default: ,\n)
     *
     * @return string
     */
    public static function renderKeyValue(Dialect $dialect, array $values, $connectBy=",\n")
    {
        $lines = [];

        foreach ($values as $column=>$value) {

            if ($value instanceof Expression) {
                $lines[] = $dialect->quote($column, Dialect::NAME) . " = $value";
                continue;
            }

            if (!is_array($value)) {
                $lines[] = $dialect->quote($column, Dialect::NAME) . ' = ' . $dialect->quote($value);
                continue;
            }

            $quotedValues = array_map(function ($item) use ($dialect) {
                return $dialect->quote($item);
            }, $value);

            $lines[] = $dialect->quote($column, Dialect::NAME) . ' IN (' . implode(", ",$quotedValues) . ')';
        }

        return implode($connectBy, $lines);
    }
}

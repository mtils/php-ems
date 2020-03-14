<?php

/**
 *  * Created by mtils on 15.02.20 at 07:59.
 **/

namespace Ems\Model\Database;

use Ems\Contracts\Core\Expression;
use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Core\Renderer;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\Database\JoinClause;
use Ems\Contracts\Model\Database\Parentheses;
use Ems\Contracts\Model\Database\Predicate;
use Ems\Contracts\Model\Database\Query as QueryContract;
use Ems\Contracts\Model\Database\SQLExpression;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Traversable;

use function array_push;
use function count;
use function get_class;
use function implode;
use function in_array;
use function is_string;
use function str_replace;
use function strtolower;

use const PHP_EOL;

/**
 * Class QueryRenderer
 *
 * This class renders with the help of a dialect queries (or SQLExpressions),
 */
class QueryRenderer implements Renderer
{
    /**
     * @var Dialect
     */
    private $dialect;

    /**
     * {@inheritDoc}
     *
     * @param Renderable $item
     *
     * @return bool
     **/
    public function canRender(Renderable $item)
    {
        return $item instanceof QueryContract;
    }

    /**
     * Renders $item.
     *
     * @param Renderable $item
     *
     * @return string
     *
     * @throws UnsupportedParameterException
     **/
    public function render(Renderable $item)
    {
        if (!$this->canRender($item)) {
            $msg = 'I can only render ' . QueryContract::class . ' not ' . get_class($item);
            throw new UnsupportedParameterException($msg);
        }
        /* @var QueryContract $item */
        return $this->renderToExpression($item)->toString();
    }

    /**
     * @param QueryContract $query
     *
     * @return SQLExpression
     *
     * @throws UnsupportedParameterException
     */
    public function renderToExpression(QueryContract $query)
    {
        $operation = $query->operation;
        if ($operation == 'SELECT') {
            return $this->renderSelect($query);
        }
        throw new UnsupportedParameterException("Unsupported operation '$operation'");
    }

    /**
     * @param QueryContract $query
     *
     * @return SQLExpression
     */
    public function renderSelect(QueryContract $query)
    {
        $bindings = [];

        $sql = ['SELECT ' . $this->renderColumns($query->columns)];

        $sql[] = "FROM " . $this->quote($query->table, Dialect::NAME);

        $joins = $this->renderJoins($query->joins);

        // if the join  is not empty
        if ($joinString = $joins->toString()) {
            $sql[] = $joinString;
            $this->extend($bindings, $joins->getBindings());
        }

        if (count($query->conditions)) {
            $glue = PHP_EOL . $query->conditions->boolean . ' ';
            $wherePart = $this->renderConditionString($query->conditions, $bindings, $glue);
            $sql[] = 'WHERE ' . $wherePart;
        }

        if ($groups = $query->groupBys) {
            $groupBy = $this->renderGroupBy($groups);
            $sql[]  = 'GROUP BY ' . $groupBy->toString();
            $this->extend($bindings, $groupBy->getBindings());
        }

        if (count($query->havings)) {
            $havingPart = $this->renderConditionString($query->havings, $bindings);
            $sql[] = 'HAVING ' . $havingPart;
        }

        if ($orderBys = $query->orderBys) {
            $orderBy = $this->renderOrderBy($query->orderBys);
            $sql[] = 'ORDER BY ' . $orderBy->toString();
            $this->extend($bindings, $orderBy->getBindings());
        }

        // LIMIT
        $string = implode(PHP_EOL, $sql);
        return new SQLExpression($string, $bindings);
    }

    /**
     * @param QueryContract $query
     * @param array         $values (optional)
     * @param bool          $replace (default: false)
     *
     * @return SQLExpression
     */
    public function renderInsert(QueryContract $query, array $values = [], $replace = false)
    {
        $bindings = [];
        $placeholders = [];
        $columns = [];

        $prefix = $replace ? 'REPLACE' : 'INSERT';

        $sql = ["$prefix INTO " . $this->quote($query->table, Dialect::NAME)];

        foreach ($values as $column => $value) {
            $columns[] = $this->quote($column, Dialect::NAME);

            if ($value instanceof SQLExpression) {
                $placeholders[] = $value->toString();
                $this->extend($bindings, $value->getBindings());
                continue;
            }

            if ($value instanceof Expression) {
                $placeholders[] = $value->toString();
                continue;
            }

            $bindings[] = $value;
            $placeholders[] = '?';
        }

        $sql[] = '(' . implode(', ', $columns) . ')';
        $sql[] = 'VALUES (' . implode(', ', $placeholders) . ')';

        return new SQLExpression(implode("\n", $sql), $bindings);
    }

    /**
     * @param QueryContract $query
     * @param array         $values (optional)
     *
     * @return SQLExpression
     */
    public function renderUpdate(QueryContract $query, array $values = [])
    {
        $bindings = [];
        $sql = ["UPDATE " . $this->quote($query->table, Dialect::NAME) . ' SET'];
        $assignments = [];

        foreach ($values as $column => $value) {
            $prefix = $this->quote($column, Dialect::NAME) . ' = ';

            if ($value instanceof SQLExpression) {
                $assignments[] = $prefix . $value->toString();
                $this->extend($bindings, $value->getBindings());
                continue;
            }

            if ($value instanceof Expression) {
                $assignments[] = $prefix . $value->toString();
                continue;
            }

            $assignments[] = $prefix . "?";
            $bindings[] = $value;
        }

        $sql[] = implode(",\n", $assignments);

        if (count($query->conditions)) {
            $glue = PHP_EOL . $query->conditions->boolean . ' ';
            $wherePart = $this->renderConditionString($query->conditions, $bindings, $glue);
            $sql[] = 'WHERE ' . $wherePart;
        }

        return new SQLExpression(implode("\n", $sql), $bindings);
    }

    /**
     * @param QueryContract $query
     *
     * @return SQLExpression
     */
    public function renderDelete(QueryContract $query)
    {
        $bindings = [];
        /* @noinspection SqlWithoutWhere */
        $sql = ["DELETE FROM " . $this->quote($query->table, Dialect::NAME)];

        if (count($query->conditions)) {
            $glue = PHP_EOL . $query->conditions->boolean . ' ';
            $wherePart = $this->renderConditionString($query->conditions, $bindings, $glue);
            $sql[] = 'WHERE ' . $wherePart;
        }

        return new SQLExpression(implode("\n", $sql), $bindings);
    }

    /**
     * Create a string ou of columns.
     *
     * @param array|Traversable $columns
     * @param array             $bindings (optional)
     *
     * @return string
     */
    public function renderColumns($columns = [], array &$bindings = [])
    {
        if (!$columns || $columns === '*' || $columns === ['*']) {
            return '*';
        }

        $strings = [];

        foreach ($columns as $column) {
            if ($column instanceof Expression) {
                $strings[] = $this->renderExpression($column, $bindings);
                continue;
            }
            // TODO Support (and test) Query objects here too
            $strings[] = $this->quote($column, Dialect::NAME);
        }

        return implode(', ', $strings);
    }

    /**
     * @param JoinClause[] $joins
     *
     * @return SQLExpression
     */
    public function renderJoins($joins = [])
    {

        $lines = [];
        $bindings = [];

        foreach ($joins as $join) {
            $line  = $join->direction ? $join->direction : '';
            if ($join->unification) {
                $line .= " $join->unification";
            }

            $line .= ' JOIN ' . $this->quote($join->table, Dialect::NAME);

            if ($join->alias) {
                $line .= ' AS ' . $this->quote($join->alias, Dialect::NAME);
            }

            $lines[] = trim($line);

            if (!count($join->conditions)) {
                continue;
            }


            $lines[] = 'ON ' .  $this->renderConditionString($join->conditions, $bindings);
        }//end foreach

        return new SQLExpression(trim(implode(PHP_EOL, $lines)), $bindings);
    }

    /**
     * @param array|Traversable $conditions
     * @param string $glue (default: PHP_EOL)
     *
     * @return SQLExpression
     *
     * @throws UnsupportedParameterException
     */
    public function renderConditions($conditions, $glue = null)
    {
        if ($glue === null && $conditions instanceof Parentheses && $conditions->boolean) {
            $glue = PHP_EOL . trim($conditions->boolean) . ' ';
        }

        if ($glue === null) {
            $glue = PHP_EOL;
        }

        $bindings = [];
        $string = $this->renderConditionString($conditions, $bindings, $glue);

        return new SQLExpression($string, $bindings);
    }

    public function renderGroupBy(array $groupBys = [])
    {
        if (!$groupBys) {
            return new SQLExpression();
        }
        $lines = [];
        $bindings = [];

        foreach ($groupBys as $expression) {
            if ($expression instanceof Expression) {
                $lines[] = $this->renderExpression($expression, $bindings);
                continue;
            }
            $lines[] = $this->quote($expression, Dialect::NAME);
        }

        return new SQLExpression(implode(',', $lines), $bindings);
    }

    public function renderOrderBy(array $orderBys = [])
    {
        if (!$orderBys) {
            return new SQLExpression();
        }
        $lines = [];
        $bindings = [];
        foreach ($orderBys as $key => $direction) {
            if ($direction instanceof Expression) {
                $lines[] = $this->renderExpression($direction, $bindings);
                continue;
            }
            $lines[] = $this->quote($key, Dialect::NAME) . " $direction";
        }
        return new SQLExpression(implode(',', $lines), $bindings);
    }

    /**
     * Return the assigned dialect
     *
     * @return Dialect
     */
    public function getDialect()
    {
        return $this->dialect;
    }

    /**
     * Set the dialect to render the expression.
     *
     * @param Dialect $dialect
     *
     * @return $this
     */
    public function setDialect(Dialect $dialect)
    {
        $this->dialect = $dialect;
        return $this;
    }

    /**
     * @param array|Traversable $conditions
     * @param array $bindings
     * @param string $glue (default: PHP_EOL)
     *
     * @return string
     *
     * @throws UnsupportedParameterException
     */
    protected function renderConditionString($conditions, array &$bindings = [], $glue = PHP_EOL)
    {

        $lines = [];

        foreach ($conditions as $condition) {
            if ($condition instanceof Predicate) {
                $lines[] = $this->renderPredicate($condition, $bindings);
                continue;
            }

            if ($condition instanceof Expression) {
                $lines[] = $this->renderExpression($condition, $bindings);
                continue;
            }

            if (Type::isStringable($condition)) {
                $lines[] = $condition;
                continue;
            }

            if (!$condition instanceof Parentheses) {
                throw new UnsupportedParameterException('Unexpected condition type ' . Type::of($condition));
            }

            $prefix = '';
            if ($glue == PHP_EOL && count($lines)) {
                $prefix = 'AND ';
            }

            $lines[] = $prefix . $this->renderConditionString($condition, $bindings, "\n" . $condition->boolean . ' ');
        }//end foreach

        return implode($glue, $lines);
    }

    protected function renderPredicate(Predicate $predicate, array &$bindings = [])
    {

        $start = $this->renderPredicatePart($predicate->left, $bindings);

        $mode = $predicate->rightIsKey ? 'key' : 'value';
        $operator = $predicate->operator;

        if (!$operator) {
            return $start;
        }

        if ($this->isInOperator($operator)) {
            $mode = 'in';
        }

        if ($nullExpression = $this->getNullComparison($predicate)) {
            return "$start $nullExpression";
        }

        $end = $this->renderPredicatePart($predicate->right, $bindings, $mode);

        return "$start $predicate->operator $end";
    }

    protected function renderPredicatePart($operand, &$bindings = [], $mode = 'key')
    {
        if ($operand instanceof Expression) {
            return $this->renderExpression($operand, $bindings);
        }

        if ($mode == 'key') {
            return $this->quote($operand, Dialect::NAME);
        }

        if ($mode == 'value') {
            $bindings[] = $operand;
            return '?';
        }

        if ($mode == 'in') {
            return $this->renderIn($operand, $bindings);
        }

        throw new UnsupportedParameterException("Unknown mode '$mode'");
    }

    protected function renderIn($parameters, &$bindings = [])
    {
        $parameters = is_string($parameters) ? [$parameters] : $parameters;
        $questionMarks = [];

        foreach ($parameters as $parameter) {
            $questionMarks[] = '?';
            $bindings[] = $parameter;
        }

        return '(' . implode(',', $questionMarks) . ')';
    }

    /**
     * @param SQLExpression $expression
     * @param array $bindings
     *
     * @return string
     *
     * phpcs:disable Generic.NamingConventions.CamelCapsFunctionName
     */
    protected function renderSQLExpression(SQLExpression $expression, array &$bindings)
    {
        foreach ($expression->getBindings() as $key => $binding) {
            $bindings[] = $binding;
        }
        return $expression->toString();
        // phpcs:enable
    }


    protected function renderExpression(Expression $expression, array &$bindings = [])
    {
        if ($expression instanceof SQLExpression) {
            return $this->renderSQLExpression($expression, $bindings);
        }

        if ($this->dialect) {
            return $this->dialect->render($expression, $bindings);
        }

        return $expression->toString();
    }

    /**
     * Quote with or without a dialect. Does not quote identifiers
     * (tables, columns, ...) so do this on your own if you don't
     * use a dialect.
     *
     * @param string|Expression $string
     * @param string            $type    (default: 'string')
     *
     * @return string
     */
    protected function quote($string, $type = 'string')
    {
        if ($string instanceof Expression) {
            return $string->toString();
        }
        if ($this->dialect) {
            return $this->dialect->quote($string, $type);
        }
        return $type == 'string' ? "'" . str_replace("'", "\'", $string) . "'" : $string;
    }

    protected function extend(array &$bindings, array $new)
    {
        array_push($bindings, ...$new);
    }

    /**
     * @param string $operator
     *
     * @return bool
     */
    protected function isInOperator($operator)
    {
        $operator = strtolower($operator);
        return $operator == 'in' || $operator == 'not in';
    }

    /**
     * @param Predicate $predicate
     *
     * @return string
     */
    protected function getNullComparison(Predicate $predicate)
    {
        if ($predicate->right !== null) {
            return '';
        }

        $operator = strtolower($predicate->operator);

        if (in_array($operator, ['in', '='])) {
            return 'IS NULL';
        }

        if (in_array($operator, ['not in', '!=', '<>'])) {
            return 'IS NOT NULL';
        }

        return '';
    }
}

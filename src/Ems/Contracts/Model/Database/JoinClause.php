<?php
/**
 *  * Created by mtils on 23.12.19 at 05:48.
 **/

namespace Ems\Contracts\Model\Database;


use Closure;
use Ems\Contracts\Core\Expression;
use Ems\Contracts\Core\Stringable;
use Ems\Model\Database\Query as ModelQuery;

use function func_get_args;
use function is_string;

/**
 * Class JoinClause
 *
 * @package Ems\Contracts\Model\Database
 *
 * @property      string|Stringable    table
 * @property      string               alias
 * @property      string               direction (LEFT|RIGHT|FULL)
 * @property      string               unification (INNER|OUTER|CROSS)
 * @property-read Parentheses          conditions
 * @property-read string               id Either the table or its alias
 */
class JoinClause
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var string|Stringable
     */
    protected $table = '';

    /**
     * @var string
     */
    protected $alias = '';

    /**
     * @var string
     */
    protected $direction = '';

    /**
     * @var string
     */
    protected $unification = '';

    /**
     * @var Parentheses
     */
    protected $conditions;

    /**
     * JoinClause constructor.
     *
     * @param string $table (optional)
     * @param Query  $query (optional)
     */
    public function __construct($table = '', Query $query = null)
    {
        $this->table = $table;
        $this->query = $query;
        $this->conditions = new Parentheses('AND');
    }

    /**
     * Set the on condition(s).
     *
     * @param string|Predicate $left
     * @param string           $operatorOrRight
     * @param string           $right
     *
     * @return $this
     */
    public function on($left, $operatorOrRight = '', $right = '')
    {
        if ($left instanceof Predicate) {
            $this->conditions->where($left);
            return $this;
        }
        $predicate = new Predicate(...func_get_args());
        $this->conditions->where($predicate->rightIsKey(true));
        return $this;
    }

    /**
     * Append a new braced group of expressions to the on clause.
     * Either use a callable to add your expressions or use the return value.
     *
     * @param string   $boolean (and|or)
     * @param callable $builder (optional)
     *
     * @return Parentheses
     *
     * @see Parentheses::__invoke()
     */
    public function __invoke($boolean = 'AND', callable $builder = null)
    {

        $countBefore = count($this->conditions);
        $group = $this->conditions->__invoke($boolean, $builder);

        if ($countBefore !== 0) {
            return $group;
        }
        // There were no condition before this call
        // So we assume the on() method was not called and only
        // $clause('AND', f()) was called
        $first = $group->first();

        if (!$first instanceof Predicate) {
            return $group;
        }

        // ...and if the right operand is just a string it's probably a column
        if (is_string($first->right)) {
            $first->rightIsKey();
        }

        return $group;
    }

    /**
     * Set an alias for the table.
     *
     * @param string $alias
     *
     * @return $this
     */
    public function as($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Make the join a left join.
     *
     * @return $this
     */
    public function left()
    {
        $this->direction = 'LEFT';
        return $this;
    }

    /**
     * Make the join a right.
     *
     * @return $this
     */
    public function right()
    {
        $this->direction = 'RIGHT';
        return $this;
    }

    /**
     * Make the join a full join.
     *
     * @return $this
     */
    public function full()
    {
        $this->direction = 'FULL';
        return $this;
    }

    /**
     * Make it an inner join.
     *
     * @return $this
     */
    public function inner()
    {
        $this->unification = 'INNER';
        return $this;
    }

    /**
     * Make it an outer join.
     *
     * @return $this
     */
    public function outer()
    {
        $this->unification = 'OUTER';
        return $this;
    }

    /**
     * Make it an cross join.
     *
     * @return $this
     */
    public function cross()
    {
        $this->unification = 'CROSS';
        return $this;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'table':
                return $this->table;
            case 'alias':
                return $this->alias;
            case 'direction':
                return $this->direction;
            case 'unification':
                return $this->unification;
            case 'conditions':
                return $this->conditions;
            case 'id':
                return $this->alias ? (string)$this->alias : (string)$this->table;
        }
        return null;
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'table':
                $this->table = $value;
                return;
            case 'alias':
                $this->alias = $value;
                return;
            case 'direction':
                $this->direction = $value;
                return;
            case 'unification':
                $this->unification = $value;
                return;
        }
    }

    // The following method are just to allow a fluid syntax if you work with
    // $query->join

    /**
     * Perform the select call on the passed query.
     *
     * @param string[]|Stringable[] ...$columns
     *
     * @return Query|ModelQuery
     */
    public function select(...$columns)
    {
        return $this->query->select(...$columns);
    }

    /**
     * Set the table on the passed query.
     *
     * @param string|Expression $table
     *
     * @return Query|ModelQuery
     */
    public function from($table)
    {
        return $this->query->from($table);
    }

    /**
     * Perform a join call on the passed query.
     *
     * @param string|Expression|JoinClause $table
     *
     * @return JoinClause
     */
    public function join($table)
    {
        return $this->query->join($table);
    }

    /**
     * Call where on the passed query.
     *
     * @param string|Expression|Closure $operand
     * @param mixed $operatorOrValue (optional)
     * @param mixed $value (optional)
     *
     * @return Query|ModelQuery
     **/
    public function where($operand, $operatorOrValue = null, $value = null)
    {
        return $this->query->where(...func_get_args());
    }

    /**
     * Call groupBy on the passed query.
     *
     * @param mixed ...$column
     *
     * @return Query|ModelQuery
     */
    public function groupBy(...$column)
    {
        return $this->query->groupBy(...$column);
    }

    /**
     * Call orderBy on the passed query
     *
     * @param string|Stringable|array $column
     * @param string                  $direction (default:ASC)
     *
     * @return Query|ModelQuery
     */
    public function orderBy($column, $direction = 'ASC')
    {
        return $this->query->orderBy($column, $direction);
    }

    /**
     * Call having() on the passed query.
     *
     * @param string|Expression|Closure $operand
     * @param mixed                     $operatorOrValue (optional)
     * @param mixed                     $value (optional)
     *
     * @return Query|ModelQuery
     */
    public function having($operand, $operatorOrValue = null, $value = null)
    {
        return $this->query->having(...func_get_args());
    }

    /**
     * Call offset() on the passed query.
     *
     * @param int|string|Stringable|null $offset
     * @param int|string|Stringable      $limit (optional)
     *
     * @return Query|ModelQuery
     */
    public function offset($offset, $limit = null)
    {
        return $this->query->offset(...func_get_args());
    }

    /**
     * Call limit() on the passed query.
     *
     * @param int|string|Stringable|null $offset
     * @param int|string|Stringable      $limit (optional)
     *
     * @return Query|ModelQuery
     */
    public function limit($limit, $offset = null)
    {
        return $this->query->limit(...func_get_args());
    }

    /**
     * Call values() on the passed query.
     *
     * @param string|array $key
     * @param mixed        $value (optional)
     *
     * @return Query|ModelQuery
     */
    public function values($key, $value = null)
    {
        return $this->query->values(...func_get_args());
    }
}

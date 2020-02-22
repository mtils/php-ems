<?php
/**
 *  * Created by mtils on 23.12.19 at 05:48.
 **/

namespace Ems\Contracts\Model\Database;


use Ems\Contracts\Core\Stringable;
use function func_get_args;

/**
 * Class JoinClause
 * @package Ems\Contracts\Model\Database
 *
 * @property      string|Stringable    table
 * @property      string               alias
 * @property      string               direction (LEFT|RIGHT|FULL)
 * @property      string               unification (INNER|OUTER|CROSS)
 * @property-read Parentheses          conditions
 * @property-read string               id Either the table or its alias
 *
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
    public function __construct($table='', Query $query=null)
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
    public function on($left, $operatorOrRight='', $right='')
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
     * @see Parentheses::__invoke()
     *
     * @param string   $boolean (and|or)
     * @param callable $builder (optional)
     *
     * @return Parentheses
     */
    public function __invoke($boolean='AND', callable $builder=null)
    {
        return $this->conditions->__invoke($boolean, $builder);
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
}
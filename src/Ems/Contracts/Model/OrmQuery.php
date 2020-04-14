<?php
/**
 *  * Created by mtils on 11.04.20 at 08:46.
 **/

namespace Ems\Contracts\Model;

use Closure;
use Ems\Contracts\Core\Expression;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Expression\Queryable;

use Ems\Contracts\Model\Database\Parentheses;

use function func_get_args;
use function is_array;

/**
 * Class OrmQuery
 *
 * The OrmQuery is the "object based" counterpart if the
 * Query.
 *
 * Like the Query it is a pure value object that temporary stores
 * the criteria in properties.
 *
 * @package Ems\Contracts\Model\Database
 */
class OrmQuery implements Queryable
{
    /**
     * @var string
     */
    protected $ormClass = '';

    /**
     * @var string[]
     */
    protected $withs = [];

    /**
     * @var string[]
     */
    protected $appends = [];

    /**
     * @var Parentheses
     */
    protected $conditions;

    /**
     * @var array
     */
    protected $orderBys = [];

    public function __construct($ormClass='')
    {
        $this->from($ormClass);
        $this->conditions = new Parentheses('AND');
    }

    /**
     * Set the class you are selecting from.
     *
     * @param string $ormClass
     *
     * @return $this
     */
    public function from($ormClass)
    {
        $this->ormClass = $ormClass;
        return $this;
    }

    /**
     * Pass the relation name to auto join to that relation. Do not pass
     * the class name of the foreign object. This would be ambiguous.
     * For deep nested joins use dots. (->with('projects.owner.address')).
     * With means "do select the columns of this relation within the select".
     *
     * @param string ...$relationNames
     *
     * @return $this
     */
    public function with(...$relationNames)
    {
        foreach ($relationNames as $relationName) {
            $this->withs[] = $relationName;
        }
        return $this;
    }

    /**
     * Pass a relation name to append the related objects after query the database.
     * The builders will then search for all ids in the result and append the
     * missing objects by a second WHERE IN query.
     *
     * @param string ...$relationNames
     *
     * @return $this
     */
    public function append(...$relationNames)
    {
        foreach ($relationNames as $relationName) {
            $this->appends[] = $relationName;
        }
        return $this;
    }

    /**
     * Add a filter to the query. Feel free to use property names instead of
     * columns and dotted syntax like ->where('address.city.name', 'like', '%york')
     *
     * @param string|Expression|Closure $operand
     * @param mixed $operatorOrValue (optional)
     * @param mixed $value (optional)
     *
     * @return $this
     **/
    public function where($operand, $operatorOrValue = null, $value = null)
    {
        $this->conditions->where(...func_get_args());
        return $this;
    }

    /**
     * Append a new braced group of expressions to the where clause.
     * Either use a callable to add your expressions or use the return value.
     *
     * @param string   $boolean (and|or)
     * @param callable $builder (optional)
     *
     * @return Parentheses
     *
     * @see Parentheses::__invoke()
     */
    public function __invoke($boolean, callable $builder = null)
    {
        return $this->conditions->__invoke($boolean, $builder);
    }

    /**
     * Add one or many order by statements. Use property names and dotted syntax
     * (if needed). ->orderBy('address.city.name')
     *
     * @param string|Stringable|array $column
     * @param string                  $direction (default:ASC)
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'ASC')
    {

        if (!is_array($column)) {
            return $this->orderBy([$column => $direction]);
        }

        foreach ($column as $key => $direction) {
            $this->orderBys[$key] = $direction;
        }

        return $this;
    }
}
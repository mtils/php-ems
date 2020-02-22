<?php
/**
 *  * Created by mtils on 24.12.19 at 14:27.
 **/

namespace Ems\Contracts\Model\Database;


use ArrayIterator;
use Closure;
use Countable;
use Ems\Contracts\Core\Expression;
use Ems\Contracts\Expression\Queryable;
use Exception;
use IteratorAggregate;
use Traversable;
use function call_user_func;
use function func_get_args;

/**
 * Class ParentheticalExpression
 *
 * @package Ems\Contracts\Model\Database
 *
 * @property-read string                    boolean
 * @property-read Predicate[]|Parentheses[] expressions
 */
class Parentheses implements IteratorAggregate, Queryable, Countable
{
    /**
     * The connector between the expressions (typically AND|OR)
     *
     * @var string
     */
    protected $boolean = '';

    /**
     * @var Predicate[]|Parentheses[]
     */
    protected $expressions = [];

    /**
     * Parentheses constructor.
     *
     * @param string $boolean (optional)
     * @param array  $expressions (optional)
     */
    public function __construct($boolean='', $expressions=[])
    {
        $this->boolean = $boolean;
        $this->expressions = $expressions;
    }

    /**
     * {@inheritDoc}
     *
     * @param string|Expression|Closure $operand
     * @param mixed $operatorOrValue (optional)
     * @param mixed $value (optional)
     *
     * @return self
     **/
    public function where($operand, $operatorOrValue = null, $value = null)
    {
        $predicate = $operand instanceof Predicate ? $operand : new Predicate(...func_get_args());
        $this->expressions[] = $predicate;
        return $this;
    }

    /**
     * Append a new braced group of expressions. Either use a callable
     * to add your expressions or use the return value.
     *
     * @example $query('or', function (Parentheses $group) {
     *    $group->where('foo', '<>', 'bar');
     * });
     *
     * @example $query('or')->where('foo', '<>', 'bar');
     *
     *
     * @param string   $boolean (and|or)
     * @param callable $builder (optional)
     *
     * @return $this
     */
    public function __invoke($boolean, callable $builder=null)
    {
        $group = new static($boolean);
        if ($builder) {
            call_user_func($builder, $group);
        }
        $this->expressions[] = $group;
        return $group;
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @throws Exception on failure.
     * @since 5.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->expressions);
    }

    /**
     * Count elements of an object
     * @link https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1
     */
    public function count()
    {
        return count($this->expressions);
    }

    /**
     * Clear all expressions.
     */
    public function clear()
    {
        $this->expressions = [];
    }

    /**
     * @return Parentheses|Predicate|null
     */
    public function first()
    {
        return isset($this->expressions[0]) ? $this->expressions[0] : null;
    }

    /**
     * @param string $name
     *
     * @return Predicate[]|string|null
     */
    public function __get($name)
    {
        if ($name === 'boolean' || $name === 'bool') {
            return $this->boolean;
        }

        if ($name === 'expressions') {
            return $this->expressions;
        }

        return null;
    }
}
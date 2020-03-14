<?php

/**
 *  * Created by mtils on 24.12.19 at 14:27.
 **/

namespace Ems\Contracts\Model\Database;

use ArrayIterator;
use Closure;
use Countable;
use Ems\Contracts\Core\Expression;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Expression\Queryable;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Exception;
use IteratorAggregate;
use Traversable;

use function call_user_func;
use function func_get_args;
use function func_num_args;
use function is_string;

/**
 * Class ParentheticalExpression
 *
 * @package Ems\Contracts\Model\Database
 *
 * @property-read string                                 boolean
 * @property-read Predicate[]|Parentheses[]|Expression[] expressions
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
     * @var Predicate[]|Parentheses[]|Expression[]
     */
    protected $expressions = [];

    /**
     * Parentheses constructor.
     *
     * @param string $boolean (optional)
     * @param array  $expressions (optional)
     */
    public function __construct($boolean = '', $expressions = [])
    {
        $this->boolean = $boolean;
        $this->expressions = $expressions;
    }

    /**
     * {@inheritDoc}
     *
     * @param string|Expression|Closure|Predicate $operand
     * @param mixed $operatorOrValue (optional)
     * @param mixed $value (optional)
     *
     * @return self
     *
     * @throws UnsupportedParameterException
     **/
    public function where($operand, $operatorOrValue = null, $value = null)
    {
        if (func_num_args() !== 1) {
            $this->expressions[] = new Predicate(...func_get_args());
            return $this;
        }

        if ($operand instanceof Expression || $operand instanceof Predicate || is_string($operand)) {
            $this->expressions[] = $operand;
            return $this;
        }

        throw new UnsupportedParameterException('I do not support ' . Type::of($operand));
    }

    /**
     * Append a new braced group of expressions. Either use a callable
     * to add your expressions or use the return value.
     *
     * @param string   $boolean (and|or)
     * @param callable $builder (optional)
     *
     * @return $this
     *
     * @example $query('or', function (Parentheses $group) {
     *    $group->where('foo', '<>', 'bar');
     * });
     *
     * @example $query('or')->where('foo', '<>', 'bar');
     */
    public function __invoke($boolean, callable $builder = null)
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
     *
     * @link   https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or <b>Traversable</b>
     * @throws Exception on failure.
     * @since  5.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->expressions);
    }

    /**
     * Count elements of an object
     *
     * @link   https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * @since  5.1
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

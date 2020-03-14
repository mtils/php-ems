<?php
/**
 *  * Created by mtils on 22.12.19 at 06:27.
 **/

namespace Ems\Contracts\Model\Database;


use Ems\Contracts\Core\Expression;
use Ems\Contracts\Core\StringableTrait;
use function array_merge;
use function is_array;
use function strtolower;

/**
 * Class SQLExpression
 *
 * An SQL Expression is an expression with bindings.
 *
 * @package Ems\Contracts\Model\Database
 */
class SQLExpression implements Expression
{
    use StringableTrait;

    /**
     * @var Dialect
     */
    protected $dialect;

    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * @var string
     */
    protected $rawString = '';

    public function __construct($rawString='', array $bindings=[])
    {
        $this->rawString = $rawString;
        $this->bindings = $bindings;
    }

    /**
     * Return the bindings of this expression.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    public function bind($key, $value='')
    {
        if (!is_array($key)) {
            return $this->bind([$key => $value]);
        }
        $this->bindings = array_merge($this->bindings, $key);
        return $this;
    }

    /**
     * Replace all bindings with the passed ones.
     *
     * @param array $bindings
     *
     * @return $this
     */
    public function setBindings(array $bindings)
    {
        $this->bindings = $bindings;
        return $this;
    }



    /**
     * {@inheritDoc}
     *
     * @return string
     **/
    public function toString()
    {
        return $this->rawString;
    }


}
<?php


namespace Ems\Core;

use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Contracts\Core\StringableTrait;

class KeyExpression implements ExpressionContract
{
    use StringableTrait;

    /**
     * @var string
     **/
    protected $key = '';

    /**
     * @var string
     */
    protected $alias = '';

    /**
     * @param string $key   (optional)
     * @param string $alias (optional)
     **/
    public function __construct($key='', $alias='')
    {
        $this->key = $key;
        $this->alias = $alias;
    }

    /**
     * Return the key
     *
     * @return string
     **/
    public function toString()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function alias()
    {
        return $this->alias;
    }
}

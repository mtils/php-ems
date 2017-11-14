<?php


namespace Ems\Core;

use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Core\Support\StringableTrait;

class KeyExpression implements ExpressionContract
{
    use StringableTrait;

    /**
     * @var string
     **/
    protected $key;

    /**
     * @param string $key
     **/
    public function __construct($key='')
    {
        $this->key = $key;
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
}

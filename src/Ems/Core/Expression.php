<?php


namespace Ems\Core;

use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Core\Support\StringableTrait;

class Expression implements ExpressionContract
{
    use StringableTrait;

    /**
     * @var string
     **/
    protected $raw;

    /**
     * @param string $raw
     **/
    public function __construct($raw='')
    {
        $this->raw = $raw;
    }

    /**
     * Return the raw string
     *
     * @return string
     **/
    public function toString()
    {
        return (string)$this->raw;
    }
}

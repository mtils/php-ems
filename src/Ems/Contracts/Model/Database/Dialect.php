<?php


namespace Ems\Contracts\Model\Database;

use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Expression;
use Exception;

/**
 * A Dialect knows how to speak to a specific database.
 * The __toString() method has to return the name.
 **/
interface Dialect extends Stringable
{

    /**
     * @var string
     **/
    const STR = 'string';

    /**
     * @var string
     **/
    const NAME = 'name';

    /**
     * Quote a string or table/column/database name
     *
     * @param string $string
     * @param string $type (default: string) Can be string|name
     *
     * @return string
     **/
    public function quote($string, $type='string');

    /**
     * Render an expression to a string and add the bindings to
     * the $bindings array you pass.
     *
     * @param Expression $expression
     * @param array      $bindings (optional)
     *
     * @return string
     **/
    public function render(Expression $expression, array &$bindings=[]);

    /**
     * Return the name of this dialect.
     *
     * @return string
     **/
    public function name();

    /**
     * Return the timestamp format of this database.
     *
     * @return string
     **/
    public function timeStampFormat();

    /**
     * Create an exception caused by an error from a connection using this
     * dialect.
     *
     * @param NativeError $error
     * @param Exception  $original (optional)
     *
     * @return SQLException
     **/
    public function createException(NativeError $error, Exception $original=null);
}

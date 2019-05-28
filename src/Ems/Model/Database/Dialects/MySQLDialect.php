<?php

namespace Ems\Model\Database\Dialects;

use InvalidArgumentException;
use function str_replace;

/**
 * Class MySQLDialect
 *
 * CAUTION: This class is not complete. No exception conversion or other special
 * things are implemented here.
 *
 * @package Ems\Model\Database\Dialects
 */
class MySQLDialect extends AbstractDialect
{

    /**
     * {@inheritdoc}
     *
     * @param string $string
     * @param string $type (default: string) Can be string|name
     *
     * @return string
     **/
    public function quote($string, $type='string')
    {
        if ($type == 'name') {
            return '`'.str_replace('`', '``', $string).'`';
        }

        if ($type != 'string') {
            throw new InvalidArgumentException("type has to be either string|name, not $type");
        }

        $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
        $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

        return '"' . str_replace($search, $replace, $string) . '"';

    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function name()
    {
        return 'mysql';
    }

    /**
     * Return the timestamp format of this database.
     *
     * @return string
     **/
    public function timeStampFormat()
    {
        return 'Y-m-d H:i:s';
    }

}

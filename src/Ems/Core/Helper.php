<?php

namespace Ems\Core;

use Traversable;
use ArrayAccess;
use Countable;

class Helper
{

    /**
     * @var array
     **/
    protected static $camelCache = [];

    /**
     * @var array
     **/
    protected static $studlyCache = [];

    /**
     * @var array
     **/
    protected static $snakeCache = [];

    /**
     * Call a callable (faster)
     *
     * @param callable $callable
     * @param array    $args
     *
     * @return mixed
     **/
    public static function call(callable $callable, $args=[])
    {
        if (!is_array($args)) {
            $args = [$args];
        }

        switch (count($args)) {
            case 0:
                return call_user_func($callable);
            case 1:
                return call_user_func($callable, $args[0]);
            case 2:
                return call_user_func($callable, $args[0], $args[1]);
            case 3:
                return call_user_func($callable, $args[0], $args[1], $args[2]);
            case 4:
                return call_user_func($callable, $args[0], $args[1], $args[2], $args[3]);
        }

        return call_user_func_array($callable, $args);
    }

    /**
     * Converts a name to camel case (first letter is lowercase)
     *
     * @param string $value
     *
     * @return string
     **/
    public static function camelCase($value)
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        static::$camelCache[$value] = lcfirst(static::studlyCaps($value));

        return static::$camelCache[$value];
    }

    /**
     * Converts a name to studly caps (camel case first letter uppercase)
     *
     * @param string $value
     *
     * @return string
     **/
    public static function studlyCaps($value)
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        static::$studlyCache[$key] = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));

        return static::$studlyCache[$key];
    }

    /**
     * Convert a string to snake case.
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    public static function snake_case($value, $delimiter = '_')
    {
        $key = $value;

        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }

        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', $value);

            $value = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * Return the class without namespace
     *
     * @param  string|object  $class
     * @return string
     */
    public static function withoutNamespace($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Trims a word out of a string
     *
     * @param string $string
     * @param string $word
     *
     * @return string
     **/
    public static function rtrimWord($string, $word)
    {
        $end = mb_substr($string, 0-mb_strlen($word));
        if ($end != $word) {
            return $string;
        }
        return mb_substr($string, 0, mb_strlen($string)-mb_strlen($word));
    }

    /**
     * Return true if the passed object or array is a sequence
     *
     * @param mixed $value
     * @param bool  $strict (default:true)
     *
     * @return bool
     **/
    public static function isSequential($value, $strict=false)
    {

        if ($value === []) {
            return true;
        }

        if (!is_object($value) && !is_array($value)) {
            return false;
        }

        $hasArrayAccess = is_array($value) || $value instanceof ArrayAccess;
        $isTraversable = is_array($value) || $value instanceof Traversable;

        // An object which you cant traverse shouldnt be sequential
        if (!$isTraversable) {
            return false;
        }

        if ($strict && $hasArrayAccess) {
            $keys = static::keys($value);
            return $keys === range(0, count($keys) - 1);
        }

        foreach ($value as $key=>$unused) {
            return $key === 0;
        }

        // Traversable and empty
        return true;

    }

    /**
     * Return the name of the passed values type
     *
     * @param mixed $value
     *
     * @return string
     **/
    public static function typeName($value)
    {
        return is_object($value) ? get_class($value) : strtolower(gettype($value)); //NULL is uppercase
    }

    /**
     * Return the first item fo the passed value. (First array item, first letter,
     * first object item.
     *
     * @param mixed $value
     *
     * @return mixed
     **/
    public static function first($value)
    {

        if (is_array($value) && $value) {
            reset($value);
            return current($value);
        }

        if (!$value) {
            return;
        }

        if (is_string($value)) {
            return $value[0];
        }

        if (!is_object($value)) {
            return;
        }

        if ($value instanceof ArrayAccess && isset($value[0])) {
            return $value[0];
        }

        if (!$value instanceof Traversable) {
            return;
        }

        foreach ($value as $key=>$item) {
            return $item;
        }
    }

    /**
     * Return the keys of the passed array or object
     *
     * @param mixed $value
     *
     * @return array
     **/
    public static function keys($value)
    {

        if (is_array($value)) {
            return array_keys($value);
        }

        if (!is_object($value)) {
            return [];
        }

        if (!$value instanceof Traversable) {
            return array_keys(get_object_vars($value));
        }

        return array_keys(iterator_to_array($value, true));

    }

    /**
     * Split a (multibyte string)
     *
     * @param string $string
     * @param string $charset (default='UTF-8')
     *
     * @return array
     **/
    public static function stringSplit($string, $chunkLength=0, $charset='UTF-8')
    {

        if ($chunkLength < 1) {
            return preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY);
        }


        $split = [];

        $len = mb_strlen($string, $charset);

        for ($i = 0; $i < $len; $i += $chunkLength) {
            $split[] = mb_substr($string, $i, $chunkLength, $charset);
        }

        return $split;

    }

}

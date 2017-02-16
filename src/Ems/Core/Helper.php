<?php

namespace Ems\Core;

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

        static::$camelCache[$value] = lcfirst();
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

}

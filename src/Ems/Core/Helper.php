<?php

namespace Ems\Core;

use Ems\Core\Exceptions\UnsupportedParameterException;
use Traversable;
use ArrayAccess;
use Countable;
use Ems\Contracts\Core\Extractor as ExtractorContract;

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
     * @var ExtractorContract
     **/
    protected static $extractor;

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
     * Return if the passed value is a string (has __toString method)
     *
     * @param mixed $value
     *
     * @return bool
     **/
    public static function isStringLike($value)
    {
        return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
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
     * Return true if $value contains $anyOfThis
     *
     * @param mixed        $value
     * @param string|array $anyOfThis
     *
     * @return bool
     **/
    public static function contains($value, $anyOfThis)
    {

        $anyOfThis = (array)$anyOfThis;

        foreach ($anyOfThis as $needle) {

            if (is_scalar($value)) {
                if (mb_strpos("$value", $needle) !== false) {
                    return true;
                }
                continue;
            }

            if (is_array($value)) {
                if (in_array($needle, array_values($value))) {
                    return true;
                }
            }

            if (!$value instanceof \Traversable) {
                return false;
            }

            foreach ($value as $key=>$haystack) {
                if ($haystack == $needle) {
                    return true;
                }
            }
        }

        return false;

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
            return null;
        }

        if (is_string($value)) {
            return $value[0];
        }

        if (!is_object($value)) {
            return null;
        }

        if ($value instanceof ArrayAccess && isset($value[0])) {
            return $value[0];
        }

        if (!$value instanceof Traversable) {
            return null;
        }

        foreach ($value as $key=>$item) {
            return $item;
        }

        return null;

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
     * @param int    $chunkLength (default:0)
     * @param string $charset (default:'UTF-8')
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

    /**
     * Return if $value starts with $start
     *
     * @param array|string $value (objects with __toString() are also allowed)
     * @param mixed $start
     *
     * @return bool
     **/
    public static function startsWith($value, $start)
    {

        if (is_array($value)) {
            return self::first($value) == $start;
        }

        return mb_strpos("$value", "$start") === 0;

    }

    /**
     * Return an array or object value of path $path
     *
     * @param object|array $root
     * @param string $path
     *
     * @return mixed
     *
     * @see Extractor::value()
     **/
    public static function value($root, $path)
    {
        if (!static::$extractor) {
            static::$extractor = new Extractor;
        }
        return static::$extractor->value($root, $path);
    }

    /**
     * Check if the passed value has ArrayAccess.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function hasArrayAccess($value)
    {
        return (is_array($value) || $value instanceof ArrayAccess);
    }

    /**
     * Throws an exception if the passed value has no ArrayAccess.
     *
     * @param $value
     *
     * @return array|\ArrayAccess
     */
    public static function forceArrayAccess($value)
    {
        if (!static::hasArrayAccess($value)) {
            throw new UnsupportedParameterException("The passed value has no ArrayAccess: " . static::typeName($value));
        }
        return $value;
    }

    public static function offsetExists(array &$array, $key)
    {
        if (!strpos($key, '.')) {
            return array_key_exists($key, $array);
        }

        $parts = explode('.', $key);
        $last = array_pop($parts);
        $parentPath = implode('.', $parts);

        $parent = static::offsetGet($array, $parentPath);

        if (!is_array($parent)) {
            return false;
        }

        return static::offsetExists($parent, $last);

    }

    public static function offsetGet(array $array, $key, $default=null)
    {
        if ($key === null) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
                continue;
            }
            return $default;
        }

        return $array;
    }

    public static function offsetSet(array &$array, $key, $value)
    {

        if (is_null($key)) {
            return $array = $value;
        }

        $segments = explode('.', $key);

        while (count($segments) > 1) {
            $key = array_shift($segments);

            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($segments)] = $value;

        return $array;
    }

    public static function offsetUnset(array &$array, $key)
    {

        if (!strpos($key, '.')) {
            unset($array[$key]);
            return;
        }

        $segments = explode('.', $key);

        while (count($segments) > 1) {
            $key = array_shift($segments);

            if (! isset($array[$key]) || ! is_array($array[$key])) {
                return;
            }

            $array = &$array[$key];
        }

        $last = array_shift($segments);

        if (array_key_exists($last, $array)) {
            unset($array[$last]);
        }

    }

    /**
     * Set a custom extractor (for testing)
     *
     * @param ExtractorContract $extractor
     **/
    public static function setExtractor(ExtractorContract $extractor)
    {
        static::$extractor = $extractor;
    }
}

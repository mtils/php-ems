<?php
/**
 *  * Created by mtils on 17.12.17 at 08:50.
 **/

namespace Ems\Contracts\Core;

use Countable;
use Ems\Contracts\Core\Exceptions\TypeException;
use function is_bool;
use function is_numeric;
use Traversable;
use ArrayAccess;

class Type
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
     * Check if a value is of type $type. Pass multiple types to check if the
     * value has all of that types.
     *
     * @param mixed        $value
     * @param string|array $type
     * @param bool         $isNullable (default:false)
     *
     * @return bool
     */
    public static function is($value, $type, $isNullable=false)
    {
        if ($isNullable && $value === null) {
            return true;
        }

        if (is_array($type)) {
            return Map::all($type, function ($type) use (&$value, $isNullable) {
                return static::is($value, $type, $isNullable);
            });
        }

        switch ($type) {
            case 'bool':
            case 'boolean':
                return is_bool($value);
            case 'int':
                return is_int($value);
            case 'float':
                return is_float($value);
            case 'numeric':
                return is_numeric($value);
            case 'string':
                return is_string($value);
            case 'resource':
                return is_resource($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value);
            case Traversable::class:
                return is_array($value) || $value instanceof Traversable;
            case ArrayAccess::class:
                return is_array($value) || $value instanceof ArrayAccess;
            default:
                return $value instanceof $type;
        }
    }

    /**
     * Return true if the passed value is an object with a __toString() method
     * or a string.
     *
     * @param $value
     *
     * @return bool
     */
    public static function isStringLike($value)
    {
        return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
    }

    /**
     * Return true if a value can be casted to string.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function isStringable($value)
    {
        return static::isStringLike($value) || is_numeric($value) || is_bool($value) || is_null($value);
    }

    /**
     * Force the value to be type of the passed $type(s).
     * Return the value.
     *
     * @see self::is()
     *
     * @param mixed        $value
     * @param string|array $type
     * @param bool         $isNullable (default:false)
     *
     * @throws TypeException
     */
    public static function force($value, $type, $isNullable=false)
    {
        if (!static::is($value, $type, $isNullable)) {
            $should = is_array($type) ? implode(',', $type) : $type;

            /** @var TypeException $e */
            $e = static::exception("The passed value is a :type but has to be $should", $value);
            throw $e;
        }
    }

    /**
     * Force the value to be type of the passed $type(s).
     * Return the value.
     *
     * @see self::is()
     *
     * @param mixed        $value
     * @param string|array $type
     * @param bool         $isNullable (default:false)
     *
     * @return mixed
     *
     * @throws TypeException
     */
    public static function forceAndReturn($value, $type, $isNullable=false)
    {
        static::force($value, $type, $isNullable);
        return $value;
    }

    /**
     * Return the name of the passed values type
     *
     * @param mixed $value
     *
     * @return string
     **/
    public static function of($value)
    {
        return is_object($value) ? get_class($value) : strtolower(gettype($value)); //NULL is uppercase
    }

    /**
     * Return the class name without namespace.
     *
     * @param  string|object  $class
     * @return string
     */
    public static function short($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
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
     * Cast a value to boolean.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function toBool($value)
    {
        if ($value instanceof Countable) {
            return (bool)count($value);
        }

        if (!static::isStringLike($value)) {
            return (bool)$value;
        }

        $string = "$value";

        if (trim($string) == '') {
            return false;
        }

        if (in_array(strtolower($value), ['0', 'false'], true)) {
            return false;
        }

        return (bool)$string;

    }

    /**
     * Create a new exception and replace :type in $msg with the type name.
     *
     * @param string $msg
     * @param string $value
     * @param string $class
     *
     * @return \Exception
     */
    public static function exception($msg, $value, $class=TypeException::class)
    {
        $name = static::of($value);
        return new $class(str_replace(':type', $name, $msg));
    }
}
<?php
/**
 *  * Created by mtils on 17.12.17 at 08:50.
 **/

namespace Ems\Contracts\Core;

use ArrayAccess;
use Countable;
use Ems\Contracts\Core\Exceptions\TypeException;
use Traversable;

use function array_unique;
use function basename;
use function class_exists;
use function fclose;
use function get_parent_class;
use function implode;
use function in_array;
use function interface_exists;
use function is_array;
use function is_bool;
use function is_int;
use function is_numeric;
use function iterator_to_array;
use function strpos;
use function token_get_all;
use function trait_exists;
use function trim;

use const T_CLASS;
use const T_FUNCTION;
use const T_NAMESPACE;
use const T_NEW;
use const T_NS_SEPARATOR;
use const T_RETURN;
use const T_STRING;
use const T_WHITESPACE;

class Type
{

    /**
     * This is the class any anonymous class has. PHP assigns a generated class
     * name to any anonymous class, but you should not use this string. So Type
     * returns this string in some cases.
     */
    const ANONYMOUS_CLASS = '(anonymous)';

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
            case Countable::class:
                return is_array($value) || $value instanceof Countable;
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
     * Return true if a value can be cast to string.
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
     * Return true if the passed string is a class, interface or trait name.
     * For me all this things are custom types. So Type::isCustom($foo) made
     * sense as a name.
     *
     * @param string $type
     * @param bool   $autoload (default:true)
     *
     * @return bool
     */
    public static function isCustom($type, $autoload=true)
    {
        return class_exists($type, $autoload) || interface_exists($type, $autoload) || trait_exists($type, $autoload);
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
     * Make something an array.
     *
     * @param array|Traversable $value
     *
     * @return array
     *
     * @throws TypeException
     */
    public static function toArray($value)
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            $name = static::of($value);
            throw new TypeException("Can only cast Traversable and arrays to array, not $name");
        }

        return is_array($value) ? $value : iterator_to_array($value);
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

    /**
     * @param $class
     * @param bool $recursive
     * @param bool $autoload
     *
     * @return array
     */
    public static function traits($class, $recursive=false, $autoload=true)
    {

        if (!$recursive) {
            return class_uses($class, $autoload);
        }

        $traits = [];

        // Collect the "use" statements in class and parent classes
        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while ($class = get_parent_class($class));

        // Copy the found traits into an array we can alter
        $allTraits = $traits;

        foreach ($traits as $trait) {
            $allTraits += static::traitsOfTrait($trait);
        }

        return array_unique($allTraits);
    }

    /**
     * Find the (one) class that is defined in $file. Returns self::ANONYMOUS_CLASS
     * if an anonymous class is returned in $file.
     *
     * @param string $file
     *
     * @return string
     */
    public static function classInFile(string $file) : string
    {
        $handle = fopen($file, 'r');
        $namespace = $class = $buffer = '';
        $startedNamespace = false;
        $startedFunction = false;
        $classTokenSequence = [];

        while (!feof($handle)) {

            $buffer .= fread($handle, 512);
            if (strpos($buffer, '{') === false) {
                continue;
            }
            $tokens = @token_get_all($buffer);

            foreach ($tokens as $token) {

                if (!is_array($token) || $token[0] === T_WHITESPACE) {
                    continue;
                }

                $isClassToken = $token[0] === T_CLASS;

                if ($token[0] === T_FUNCTION) {
                    $startedFunction = true;
                    continue;
                }

                if (!$startedFunction && $token[0] === T_RETURN) {
                    $classTokenSequence = [T_RETURN];
                    continue;
                }

                // We add new only if previous was return
                if ($token[0] === T_NEW) {
                    $classTokenSequence = $classTokenSequence === [T_RETURN] ? [T_RETURN, T_NEW] : [];
                    continue;
                }

                // Current token is class so "return new class"
                if ($isClassToken && $classTokenSequence === [0=>T_RETURN, 1=>T_NEW]) {
                    fclose($handle);
                    return self::ANONYMOUS_CLASS;
                }

                if ($isClassToken) {
                    $classTokenSequence = [T_CLASS];
                    continue;
                }

                if ($startedNamespace) {
                    if (in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                        $namespace .= $token[1];
                        continue;
                    }
                    $startedNamespace = false;
                }

                if ($classTokenSequence === [T_CLASS]) {
                    if ($token[0] == T_STRING) {
                        $class .= trim($token[1]);
                        continue;
                    }
                    break 2;
                }

                if ($token[0] == T_NAMESPACE && !$namespace) {
                    $startedNamespace = true;
                }

            }

        }

        fclose($handle);

        return $namespace ? $namespace . '\\' . $class : $class;

    }

    /**
     * Return all traits that $trait uses.
     *
     * @param string $trait The trait "classname"
     *
     * @return array
     */
    protected static function traitsOfTrait($trait)
    {
        $traits = static::traits($trait, false, false);

        foreach ($traits as $usedTrait) {
            $traits += static::traitsOfTrait($usedTrait);
        }

        return $traits;
    }
}
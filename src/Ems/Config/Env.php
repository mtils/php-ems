<?php
/**
 *  * Created by mtils on 3/14/21 at 6:38 PM.
 **/

namespace Ems\Config;


use ArrayAccess;
use ArrayIterator;
use Ems\Config\Exception\EnvFileException;
use IteratorAggregate;
use OutOfBoundsException;
use RuntimeException;

use function array_key_exists;
use function call_user_func;
use function explode;
use function file_exists;
use function file_get_contents;
use function function_exists;
use function getenv;
use function is_array;
use function is_numeric;
use function is_resource;
use function is_string;
use function mb_str_split;
use function preg_match;
use function preg_split;
use function putenv;
use function str_replace;
use function stream_get_contents;

use const PREG_SPLIT_NO_EMPTY;

/**
 * Class Env
 *
 * Simple dotenv load class. There is an excellent dotenv lib other than this.
 * This one here is very simple and fast. All packed in one class to maintain
 * maximum performance.
 *
 * @package Ems\Config
 */
class Env implements ArrayAccess, IteratorAggregate
{
    const NO_SECTION = 0;
    const QUOTED_SECTION = 1;
    const ESCAPED_SECTION = 2;
    const WHITESPACE_SECTION = 3;
    const COMMENT_SECTION = 4;

    const ENV_ARRAY_SETTER = '_ENV';
    const SERVER_ARRAY_SETTER = '_SERVER';
    const PUTENV_SETTER = 'putenv';

    const BOOLEAN_VALUES = [
        'true'  => true,
        'TRUE'  => true,
        'True'  => true,
        'on'    => true,
        'On'    => true,
        'ON'    => true,
        'yes'   => true,
        'YES'   => true,
        'Yes'   => true,
        'false' => false,
        'FALSE' => false,
        'False' => false,
        'off'   => false,
        'OFF'   => false,
        'Off'   => false,
        'no'    => false,
        'NO'    => false,
        'No'    => false
    ];

    const NULL_VALUES = [
        'null' => true,
        'NULL' => true,
        'Null' => true
    ];

    /**
     * @var callable[]
     */
    protected static $setters = [];

    /**
     * @var bool
     */
    protected static $settersInitialized = false;

    /**
     * @var bool
     */
    private $hideMbStrSplit=false;

    /**
     * Set environment variables by this setters.
     *
     * @var string[]
     */
    protected static $setterSequence = [
        self::PUTENV_SETTER,
        self::ENV_ARRAY_SETTER,
        self::SERVER_ARRAY_SETTER
    ];

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return static::get($offset) !== null;
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return static::get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        static::set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        static::clear($offset);
    }

    /**
     * Load a file, string, file resource or an array of lines. Optionally call
     * a custom setter to assign the contained variables.
     * Returns an associative array of parsed values of this file
     *
     * @param string|resource|array $file
     * @param callable|null $setter
     *
     * @return array
     */
    public function load($file, callable $setter=null)
    {
        if (is_array($file)) {
            return $this->parseLines($file, $setter);
        }
        $string = $this->getString($file);
        return $this->load(explode("\n", $string), $setter);
    }

    /**
     * @return ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        // We use the same order like in self::get()
        $base = getenv();
        return array_merge($base, $_SERVER, $_ENV);
    }

    /**
     * Get an environment variable (from any pool). This is static because the
     * environment is.
     *
     * @param string|object $name
     * @param bool          $onlyReal (default:false)
     *
     * @return mixed
     */
    public static function get($name, $onlyReal=false)
    {
        if ($onlyReal) {
            return getenv($name, $onlyReal);
        }
        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        }
        if (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        }
        $value = getenv($name);
        return $value !== false ? $value : null;
    }

    /**
     * Set an environment variable (by the configured setters).
     *
     * @param $name
     * @param $value
     */
    public static function set($name, $value)
    {
        static::buildDefaultSettersOnce();
        foreach (static::$setterSequence as $setterName) {
            call_user_func(static::$setters[$setterName], $name, $value);
        }
    }

    /**
     * Remove an environment variable.
     *
     * @param $name
     */
    public static function clear($name)
    {
        if (function_exists('putenv')) {
            putenv($name);
        }
        unset($_ENV[$name], $_SERVER[$name]);

    }

    /**
     * Get a setter by its name.
     *
     * @param string $name
     * @return callable
     */
    public static function getSetter(string $name)
    {
        static::buildDefaultSettersOnce();
        if (isset(static::$setters[$name])) {
            return static::$setters[$name];
        }
        throw new OutOfBoundsException("Setter '$name' is not known");
    }

    /**
     * Assign a custom setter that will assign an env value.
     *
     * @param string $name
     * @param callable $setter
     */
    public static function setSetter(string $name, callable $setter)
    {
        static::$setters[$name] = $setter;
    }

    /**
     * Get the sequence how setters are called.
     *
     * @return string[]
     */
    public static function getSetterSequence()
    {
        return static::$setterSequence;
    }

    /**
     * Set the sequence the setters are called when setting a variable.
     *
     * @param string[] $sequence
     */
    public static function setSetterSequence($sequence)
    {
        static::$setterSequence = $sequence;
    }

    /**
     * Build the setter pipeline for setting env variables.
     */
    protected static function buildDefaultSettersOnce()
    {
        if (static::$settersInitialized) {
            return;
        }

        if (!isset(static::$setters[self::ENV_ARRAY_SETTER])) {
            static::$setters[self::ENV_ARRAY_SETTER] = function ($key, $value) {
                $_ENV[$key] = $value;
            };
        }

        if (!isset(static::$setters[self::SERVER_ARRAY_SETTER])) {
            static::$setters[self::SERVER_ARRAY_SETTER] = function ($key, $value) {
                $_SERVER[$key] = $value;
            };
        }

        if (!isset(static::$setters[self::PUTENV_SETTER])) {
            static::$setters[self::PUTENV_SETTER] = function ($key, $value) {
                putenv("$key=$value");
            };
        }

        static::$settersInitialized = true;

    }

    /**
     * Load a string or a direct string.
     *
     * @param $data
     * @return false|string
     */
    protected function getString($data)
    {
        if (is_resource($data)) {
            return stream_get_contents($data);
        }
        if (file_exists($data)) {
            return file_get_contents($data);
        }
        return $data;
    }

    /**
     * This method does the actual work.
     *
     * @param array $lines
     * @param callable|null $setter
     * @return array
     */
    protected function parseLines(array $lines, callable $setter=null)
    {
        $setter = $setter ?: function ($key, $value) {
            static::set($key, $value);
        };
        $parsed = [];
        $lineNumber = 1;
        foreach ($lines as $line) {
            try {
                list($key, $value) = $this->parseAssignment($line);
            } catch (RuntimeException $e) {
                throw new EnvFileException($lineNumber, '', $e);
            }
            if (!$key) {
                $lineNumber++;
                continue;
            }
            $parsed[$key] = $value;
            $setter($key, $value);
            $lineNumber++;
        }
        return $parsed;
    }

    /**
     * Parse one line of an env file.
     *
     * @param $line
     * @return array|string[]
     */
    protected function parseAssignment($line)
    {
        list($name, $value) = $this->varAndValue($line);
        if (!$name || $name[0] == '#') {
            return ['',''];
        }
        return [$this->normalizeName($name), $this->normalizeValue($value)];
    }

    /**
     * Normalize a name and remove ",',export.
     *
     * @param $name
     * @return string
     */
    protected function normalizeName($name)
    {
        return trim(str_replace(['export ', "'", '"'],'', $name));
    }

    /**
     * Normalize the right part of any .env assignment.
     *
     * @param $value
     * @return bool|float|int|string|null
     */
    protected function normalizeValue($value)
    {
        if (!$value || !is_string($value)) {
            return $value === "0" ? 0 : $value;
        }

        if ($value[0] === '"' || $value[0] === '\'') {
            return $this->parseQuotedValue($value);
        }
        return $this->parseUnquotedValue($value);
    }

    /**
     * Splits the assignment line into var and value.
     *
     * @param string $assignment
     *
     * @return array
     */
    protected function varAndValue($assignment)
    {
        $parts = explode('=', $assignment, 2);
        return [trim($parts[0]), isset($parts[1]) ? trim($parts[1]) : null];
    }

    /**
     * Parse a value that is for sure a string.
     *
     * @param $value
     * @return string
     */
    protected function parseQuotedValue($value)
    {
        $split = $this->split($value);
        $data = array_reduce($split, function ($data, $char) use ($value) {
            switch ($data[1]) {
                case self::NO_SECTION:
                    return [$data[0], self::QUOTED_SECTION];
                case self::QUOTED_SECTION:
                    if ($char === $value[0]) {
                        return [$data[0], self::WHITESPACE_SECTION];
                    }
                    if ($char === '\\') {
                        return [$data[0], self::ESCAPED_SECTION];
                    }
                    return [$data[0].$char, self::QUOTED_SECTION];

                case self::ESCAPED_SECTION:
                    if ($char === $value[0] || $char === '\\') {
                        return [$data[0].$char, self::QUOTED_SECTION];
                    }
                    return [$data[0].'\\'.$char, self::QUOTED_SECTION];

                case self::WHITESPACE_SECTION:
                    if ($char === '#') {
                        return [$data[0], self::COMMENT_SECTION];
                    }
                    return [$data[0], self::WHITESPACE_SECTION];

                case self::COMMENT_SECTION:
                    return [$data[0], self::COMMENT_SECTION];
            }
        }, ['', self::NO_SECTION]);

        return trim($data[0]);
    }

    /**
     * Parse and cat an unquoted value.
     *
     * @param $value
     * @return bool|float|int|string|null
     */
    protected function parseUnquotedValue($value)
    {
        if (strpos($value, '#') !== false) {
            $parts = explode('#', $value, 2);
            $value = count($parts) > 1 ? trim($parts[0]) : '';
        }
        if (preg_match('/\s+/', $value) < 1) {
            return $this->castValue($value);
        }
        throw new RuntimeException('.env values containing spaces must be surrounded by spaces');
    }

    /**
     * Minimal casting support on unquoted values. Boolean and numbers.
     *
     * @param $value
     * @return bool|float|int
     */
    protected function castValue($value)
    {
        $casted = $this->toBool($value);
        if ($casted !== null) {
            return $casted;
        }
        $casted = $this->toNumber($value);
        if ($casted !== null) {
            return $casted;
        }
        if (isset(self::NULL_VALUES[$value])) {
            return null;
        }
        return $value;
    }

    /**
     * @param $value
     * @return bool|null
     */
    protected function toBool($value)
    {
        if (isset(self::BOOLEAN_VALUES[$value])) {
            return self::BOOLEAN_VALUES[$value];
        }
        return null;
    }

    /**
     * @param $value
     * @return float|int|null
     */
    protected function toNumber($value)
    {
        if (!is_numeric($value)) {
            return null;
        }
        if (strpos($value, '.') !== false) {
            return (float)$value;
        }
        return (int)$value;
    }

    /**
     * @param $string
     * @return array|false|string[]|null
     */
    protected function split($string)
    {
        if (function_exists('mb_str_split') && !$this->hideMbStrSplit) {
            return mb_str_split($string);
        }
        return preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY);
    }
}
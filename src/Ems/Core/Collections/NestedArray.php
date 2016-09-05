<?php


namespace Ems\Core\Collections;


use ArrayAccess;
use Countable;
use RuntimeException;
use IteratorAggregate;
// use Collection\Iterator\ArrayIterator;
use ArrayIterator;

/**
 * A NestedArray builds a nested array from a flat one with paths.
 *
 * e.g.:
 *
 * $array = [
 *      'id'            => 13,
 *      'name'          => 'Michael',
 *      'surname'       => 'Tils',
 *      'address.id'    => 578,
 *      'address.street'=> 'Elmstreet 13',
 *      'category.id'   => 83,
 *      'category.name' => 'delivery',
 *      'category.parent.id'    => 27,
 *      'category.parent.name'  => 'worker',
 *      'age'                   => 86,
 * ];
 *
 * Will be converted to:
 *
 * $array = [
 *          'id'            => 13,
 *          'name'          => 'Michael',
 *          'surname'       => 'Tils',
 *          'address' => [
 *              'id'    => 578,
 *              'street'=> 'Elmstreet 13',
 *           ],
 *          'category' => [
 *              'id'   => 83
 *              'name' => 'delivery'
 *              'parent' => [
 *                  'id'    => 27,
 *                  'name'  => 'worker'
 *               ],
 *           ],
 *          'age'           => 86,
 * ];
 *
 * If you query the array with dots: $nestedArray['category.parent']
 * you get an nested array:
 * [
 *     'id'   => 83,
 *     'name' => 'delivery',
 *     'parent'  => [
 *         'id'   => 27
 *         'name' => 'worker'
 *     ]
 * ]
 *
 * If you query the array with a single dot you will get only the unnested
 * properties $nestedArray['.']:
 * 
 * [
 *     'id'            => 13,
 *     'name'          => 'Michael',
 *     'surname'       => 'Tils',
 *     'age'           => 86,
 * ];
 *
 * If you append a trailing dot to your query you will only get the root:
 * $nestedArray['category.parent.'] :
 *
 * [
 *     'id'   => 83,
 *     'name' => 'delivery'
 * ]
 *
 *
 * If you like to retrieve new NestedArray instances insteadof arrays call it
 * like a function:
 * $nestedArray('category.parent') => Returns new nested array with the
 * resulting array of this query
 * 
 **/
class NestedArray implements ArrayAccess, Countable, IteratorAggregate
{

    /**
     * @var array
     */
    protected $array;

    /**
     * @var array
     **/
    protected $rootCache;

    /**
     * @var array
     **/
    protected $nestedCache;

    /**
     * 
     * @var string
     **/
    protected $querySeparator = '.';

    /**
     * @var string
     **/
    protected $separator = '.';

    public function __construct(array $array, $separator='.')
    {
        $this->setSrc($array);
        $this->separator = $separator;
    }

    /**
     * Checks if index $index exists in the array. Returns true if a direct
     * key matches or the first level of the hierarchy
     *
     * @param string $offset
     * @return bool
     **/
    public function offsetExists($offset)
    {

        if ($offset == $this->querySeparator) {
            return true;
        }

        if (isset($this->array[$offset])) {
            return true;
        }

        $nested = $this->nested();

        return isset($nested[$offset]);
    }

    /**
     * Returns the value of key $offset. Goes one level deep and returns the
     * value. If it is a array it will be returned.
     *
     * @param mixed $offset
     * @return mixed
     **/
    public function offsetGet($offset)
    {

        if ($offset == $this->querySeparator) {
            return $this->root();
        }

        if (isset($this->array[$offset])) {
            return $this->array[$offset];
        }

        if (static::endsWith($offset, $this->querySeparator)) {
            $cleaned = static::removeTrailing($offset);
            return $this->sub($cleaned)->root();
        }

        return $this->group($offset);
    }

    /**
     * Setting values is not supported.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     * @throws \RuntimeException
     **/
    public function offsetSet($offset, $value)
    {
        throw new RuntimeException('Setting values is not supported');
    }

    /**
     * Unsetting values is not supported.
     *
     * @param mixed $offset
     * @return void
     * @throws \RuntimeException
     **/
    public function offsetUnset($offset)
    {
        throw new RuntimeException('Currently only reading is supported');
    }

    /**
     * Returns the count of the root
     *
     * @return int
     **/
    public function count()
    {
        return count($this->root());
    }

    /**
     * Iterates over the object
     *
     * @return \Collection\Iterator\ArrayIterator
     **/
    public function getIterator()
    {
        return new ArrayIterator($this->nested());
    }

    /**
     * Returns subgroup with name $offset. Same as offsetGet
     *
     * @param mixed $offset
     * @return array (should, but dont have to)
     **/
    public function group($offset)
    {
        return static::get(
            $this->nested(), $offset, $this->querySeparator
        );
    }

    /**
     * Returns a filtered version which contains only unnested keys
     * This is handy for request hierarchies where you have a form which
     * contains some direct properties of your model and some nested for
     * relations. root() would than return only the root values
     *
     * @return array
     **/
    public function root()
    {

        if ($this->rootCache !== null) {
            return $this->rootCache;
        }

        $this->rootCache = $this->withoutNested(
            $this->array,
            $this->separator
        );

        return $this->rootCache;

    }

    /**
     * Returns the complete nested version of the source array
     *
     * @return array
     **/
    public function nested()
    {

        if( $this->nestedCache === null) {
            $this->nestedCache = static::toNested($this->array, $this->separator);
        }

        return $this->nestedCache;

    }

    /**
     * Returns a new NestedArray from subkey $offset
     *
     * @param mixed $offset
     * @return static
     **/
    public function sub($offset)
    {
        $array = $this->group($offset);
        if (is_array($array)) {
            return new static($this->group($offset));
        }
        return new static([]);
    }

    /**
     * Callable api, same as array api but returns result array as NestedArray
     * object
     *
     * @param string $offset
     * @return static
     **/
    public function __invoke($offset='.')
    {
        if ($offset == '.') {
            return $this;
        }
        return $this->sub($offset);
    }

    /**
     * Returns the source (flat) array
     *
     * @return array
     **/
    public function getSrc()
    {
        return $this->array;
    }

    /**
     * Sets a new (flat) source array
     *
     * @param array $src
     * @return static
     **/
    public function setSrc(array $src)
    {
        $this->array = $src;
        $this->reset();
        return $this;
    }

    /**
     * Direct access to the "array-nester". Put a flat array in this method
     * and it will return a recursivly nested version
     *
     * @param array $flat
     * @param string $delimiter
     * @return array
     **/
    public static function toNested(array $flat, $delimiter = '.')
    {

        $tree = [];

        foreach ($flat as $key => $val) {

            // Get parent parts and the current leaf
            $parts = static::splitPath($key, $delimiter);
            $leafPart = array_pop($parts);

            // Build parent structure
            $parent = &$tree;

            foreach ($parts as $part) {

                if (!isset($parent[$part])) {
                    $parent[$part] = [];
                } elseif (!is_array($parent[$part])) {
                    $parent[$part] = [];
                }

                $parent = &$parent[$part];
            }

            // Add the final part to the structure
            if (empty($parent[$leafPart])) {
                $parent[$leafPart] = $val;
            }
        }

        return $tree;
    }

    /**
     * Thi is a handy method if you have an array of paths (or keys) and like
     * to have a nested template for further prozessing
     *
     * @param array $paths
     * @param string $delimiter
     * @return array
     **/
    public static function pathsToNested(array $paths, $delimiter='.')
    {

        $associative = [];

        foreach ($paths as $path) {
            $associative[$path] = [];
        }

        return static::toNested($associative, $delimiter);
    }

    /**
     * Get a key from a nested array. Query a deeply nested array with
     * property.child.name
     *
     * @param array $nested
     * @param string $key
     * @param string $delimiter
     **/
    public static function get(array $nested, $key, $delimiter='.')
    {

        if (is_null($key)) return $nested;

        if (isset($nested[$key])) return $nested[$key];

        foreach (explode($delimiter, $key) as $segment) {
            if ( ! is_array($nested) || ! array_key_exists($segment, $nested))
            {
                return;
            }

            $nested = $nested[$segment];
        }

        return $nested;
    }

    /**
     * Removes all "nested" arrays from a flat array
     *
     * @param array $flat
     * @param string $separator
     * @return array
     **/
    public static function withoutNested(array $flat, $separator='.')
    {
        $root = [];

        foreach ($flat as $key=>$value) {
            if (strpos($key, $separator) === false && !is_array($value)) {
                $root[$key] = $value;
            }
        }

        return $root;
    }

    /**
     * Converts a nested array to a dotted one
     *
     * @param array $nested The nested source array
     * @param string $connector Levels connector
     * @return array
     **/
    public static function flat(array $nested, $connector = '.')
    {
        $result = [];
        static::flatArray($result, $nested, $connector);
        return $result;
    }

    /**
     * Recursively converts nested array into a flat one with keys preserving.
     *
     * @param array $result Resulting array
     * @param array $array Source array
     * @param string $prefix Key's prefix
     * @param string $connector Levels connector
     **/
    protected static function flatArray(array &$result, array $array, $connector = '.', $prefix = null)
    {

        foreach ($array as $key => $value) {

            if (is_array($value)) {
                static::flatArray($result, $value, $connector, $prefix.$key.$connector);
                continue;
            }

            $result[$prefix.$key] = $value;

        }
    }

    /**
     * Splits the path into an array
     *
     * @param string $path
     * @param string $separator (degault '.')
     * @return array
     **/
    public static function splitPath($path, $separator='.')
    {
        $regex = '/(?<=\w)(' . preg_quote($separator, '/') . ')(?=\w)/';
        return preg_split($regex, $path, -1);//, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    }

    /**
     * Checks if the $path ends with $needle
     *
     * @param string $path
     * @param string $needle
     * @return bool
     **/
    public static function endsWith($path, $needle)
    {
        return (string) $needle === substr($path, -strlen($needle));
    }

    /**
     * Removes the last char of path
     *
     * @param string $path
     * @return string
     **/
    protected static function removeTrailing($path)
    {
        return substr($path, 0, strlen($path)-1);
    }

    /**
     * Resets the cache
     *
     * @return void
     **/
    protected function reset()
    {
        $this->rootCache = null;
        $this->nestedCache = null;
    }

}

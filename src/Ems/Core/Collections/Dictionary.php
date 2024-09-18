<?php

namespace Ems\Core\Collections;

use Countable;
use IteratorAggregate;
use ArrayAccess;
use OutOfBoundsException;
use ArrayIterator;

// use Collection\Iterator\ArrayIterator;

class Dictionary implements Countable, IteratorAggregate, ArrayAccess
{
    /**
     * Data Holder.
     *
     * @var array
     */
    protected $source = [];

    /**
     * @param Traversable|array $source
     */
    public function __construct($source = null)
    {
        if ($source) {
            $this->setSource($source);
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Countable::count()
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->source);
    }

    /**
     * (non-PHPdoc).
     *
     * @see ArrayAccess::offsetExists()
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->source);
    }

    /**
     * (non-PHPdoc).
     *
     * @see ArrayAccess::offsetUnset()
     *
     * @param mixed $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->source[$offset]);

            return;
        }
        throw new OutOfBoundsException("Offset $offset does not exist");
    }

    /**
     * (non-PHPdoc).
     *
     * @see ArrayAccess::offsetGet()
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->source[$offset];
        }
        throw new OutOfBoundsException("Offset $offset does not exist");
    }

    /**
     * (non-PHPdoc).
     *
     * @see ArrayAccess::offsetSet()
     *
     * @param mixed $offset
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->source[$offset] = $value;
    }

    /**
     * Return the source array.
     *
     * @return array
     **/
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set the source (array)
     * Array or \Traversable results in array_values() or items.
     *
     * @param array|\Traversable $source
     *
     * @return self
     **/
    public function setSource($source)
    {
        $this->source = $this->castToArray($source);

        return $this;
    }

    /**
     * (non-PHPdoc).
     *
     * @see IteratorAggregate::getIterator()
     *
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->source);
    }

    /**
     * Clears the Dictionary.
     *
     * @return self
     */
    public function clear()
    {
        $this->source = [];

        return $this;
    }

    /**
     * Copies this Dictionary or it extended classes.
     *
     * @return self
     */
    public function copy()
    {
        return new static($this->source);
    }

    /**
     * Safely get an offset. Will check isset() before.
     * If the offset does not exists it will return $default.
     *
     * @param string   $key
     * @param multiple $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        try {
            return $this->offsetGet($key);
        } catch (OutOfBoundsException $e) {
        }

        return $default;
    }

    /**
     * Sets a value with fluid syntax.
     *
     * @param mixed     $key
     * @param multitype $value
     *
     * @return self
     */
    public function set($key, $value)
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * Sets a value by reference with fluid syntax.
     *
     * @param mixed     $key
     * @param multitype $value
     *
     * @return self
     */
    public function setRef($key, &$value)
    {
        $this->source[$key] = &$value;

        return $this;
    }

    /**
     * Checks if a key exists, no matter if is_null().
     *
     * @param string $key
     *
     * @return bool
     *
     * @see Dictionary::offsetExists($offset)
     */
    public function hasKey($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Checks if a key can be assumed to be true.
     * This is a shortCut for:.
     *
     * if( isset($array['foo']) && $array['foo'] )
     *
     * This would be written like this:
     *
     * if($array->has('foo'))
     *
     * @param multitype:String|int|float $key
     *
     * @return bool
     */
    public function has($key)
    {
        if ($this->offsetExists($key) && (bool) $this->offsetGet($key)) {
            return true;
        }

        return false;
    }

    /**
     * Update the dictionary with new values.
     *
     * @param array\Traversable $newValues
     *
     * @return self
     **/
    public function update($newValues)
    {
        foreach ($newValues as $key => $value) {
            $this->offsetSet($key, $value);
        }

        return $this;
    }

    /**
     * Returns a new Dictionary object updated with the passed key(s) and value(s).
     *
     * @param mixed $key
     *
     * @see self::copy()
     *
     * @return self
     */
    public function with($key, $value = null)
    {
        $values = (is_array($key) || $key instanceof Traversable) ? $key : [$key => $value];

        return $this->copy()->update($values);
    }

    /**
     * Returns a new Dictionary object updated with the passed key(s) and value(s)
     * Alias for self::with.
     *
     * @param mixed $key
     *
     * @see self::with()
     *
     * @return self
     */
    public function updated($key, $value = null)
    {
        return $this->with($key, $value);
    }

    /**
     * Returns a new Dictionary object without the key "key".
     *
     * @param mixed $key
     *
     * @see self::copy()
     *
     * @return self
     */
    public function without($key)
    {
        $keys = func_num_args() > 1 ? func_get_args() : (array) $key;

        $copy = $this->copy();

        foreach ($keys as $key) {
            $copy->offsetUnset($key);
        }

        return $copy;
    }

    /**
     * Returns a OrderedList of key/value pairs (array($key, $value).
     *
     * @return \Ems\Core\Collections\OrderedList
     */
    public function items()
    {
        $list = new OrderedList();

        foreach ($this->source as $key => $value) {
            $list->append([$key, $value]);
        }

        return $list;
    }

    /**
     * Returns a OrderedList of keys (scalars) of the hash.
     *
     * @return \Ems\Core\Collections\OrderedList
     */
    public function keys()
    {
        return new OrderedList(array_keys($this->source));
    }

    /**
     * Remove key $key of hash if exists. If not exists, return
     * default. If no default is set a OutOfBoundsException
     * is thrown.
     *
     * @param mixed $key
     * @param mixed $default (optional
     *
     * @return mixed
     */
    public function pop($key, $default = null)
    {
        if (is_null($default)) {
            $value = $this->offsetGet($key);
            $this->offsetUnset($key);

            return $value;
        }

        if ($this->offsetExists($offset)) {
            $value = $this->offsetGet($offset);

            return $value;
        }

        return $default;
    }

    /**
     * Set the value if it does not exist in the Dictionary.
     * If it exists, return the value.
     * If it does not exist, set it with the value of
     * $default and return $default.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function returnOrSet($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->offsetGet($key);
        }
        $this->offsetSet($key, $default);

        return $default;
    }

    /**
     * Return a OrderedList of all values.
     *
     * @return Ems\Core\Collections\OrderedList
     */
    public function values()
    {
        return new OrderedList(array_values($this->source));
    }

    /**
     * Constructs a Hash with the passed keys.
     *
     * @param Traversable $keys
     *
     * @return static
     */
    public static function fromKeys($keys)
    {
        $hash = new static();
        foreach ($keys as $key) {
            $hash[$key] = null;
        }

        return $hash;
    }

    /**
     * Casts the passed $source to array.
     *
     * @param mixed $source
     *
     * @return array
     **/
    protected function castToArray($source)
    {
        if (is_array($source)) {
            return $source;
        }

        if (!$source instanceof Traversable) {
            throw new UnexpectedValueException('Source has to be \Traversable, array, string or int');
        }

        $array = [];

        foreach ($source as $key => $item) {
            $array[$key] = $item;
        }

        return $array;
    }
}

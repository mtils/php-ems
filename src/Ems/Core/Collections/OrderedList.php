<?php

namespace Ems\Core\Collections;

use Countable;
use IteratorAggregate;
use ArrayAccess;
use OutOfRangeException;
use OutOfBoundsException;
use OverflowException;
use Traversable;
use ArrayIterator;


class OrderedList implements Countable, IteratorAggregate, ArrayAccess
{

    /**
     * @var array
     **/
    protected $source = [];

    /**
     * @param array|\Traversable|int|string $source (optional)
     * @see self::setSource
     **/
    public function __construct($source = null)
    {
        if ($source) {
            $this->setSource($source);
        }
    }

    /**
     * Append a value to the end of this list
     *
     * @param mixed $value
     * @return self
     **/
    public function append($value)
    {
        $this->source[] = $value;

        return $this;
    }

    /**
     * Alias for append()
     *
     * @see self::append()
     * @param mixed $value
     * @return self
     **/
    public function push($value)
    {
        return $this->append($value);
    }

    /**
     * prepend a value to the beginning of this list
     *
     * @param mixed $value
     * @return self
     **/
    public function prepend($value)
    {
        return $this->insert(0, $value);
    }

    /**
     * Extend the list by the passed item(s)
     *
     * @param array|\Traversable
     * @return self
     **/
    public function extend($values)
    {
        foreach ($values as $value) {
            $this->append($value);
        }

        return $this;
    }

    /**
     * Insert a value at position $index
     *
     * @param int $index
     * @param mixed $value
     * @return self
     **/
    public function insert($index, $value)
    {
        $newArray = [];
        $pastInsertPosition = false;
        $count = $this->count();

        if ($index == $count) {
            $this->append($value);

            return $this;
        }

        if ($index < 0) {
            throw new OutOfRangeException("Index have to be greater than 0 not $index");
        }

        if ($index > $count) {
            throw new OverflowException("Index $index overflows greatest index $count");
        }

        for ($i = 0; $i < $count; ++$i) {
            if ($i == $index) {
                $newArray[$index] = $value;
                $newArray[$i + 1] = $this->source[$i];
                $pastInsertPosition = true;
            } else {
                if (!$pastInsertPosition) {
                    $newArray[$i] = $this->source[$i];
                } else {
                    $newArray[$i + 1] = $this->source[$i];
                }
            }
        }
        if ($pastInsertPosition) {
            $this->source = $newArray;
        }

        return $this;
    }

    /**
     * Removes a value from this list and returns it
     *
     * @param mixed $value
     * @return mixed
     **/
    public function remove($value)
    {
        return $this->pop($this->indexOf($value));
    }

    /**
     * Removes item at index $index (or its last) and returns it
     *
     * @param int $index (optional)
     * @return mixed
     **/
    public function pop($index = null)
    {
        if (is_null($index)) {
            return array_pop($this->source);
        }

        $previousValue = $this->offsetGet($index);

        $this->source = array_splice($this->source, $index, 1);

        return $previousValue;

    }

    /**
     * Finds the index of value $value
     *
     * @param mixed $value
     * @return self
     **/
    public function indexOf($value)
    {
        $count = $this->count();
        $found = false;
        for ($i = 0; $i < $count; ++$i) {
            if ($value === $this->source[$i]) {
                return $i;
            }
        }
        throw new OutOfBoundsException("Value $value not found");
    }

    /**
     * Return if this list contains $value
     *
     * @param mixed $value
     * @return bool
     **/
    public function contains($value)
    {
        try {
            return is_int($this->indexOf($value));
        } catch (OutOfBoundsException $e) {
            return false;
        }
    }

    /**
     * The amount of values. You can pass a hidden value to count
     * the occurrences of this value in this list.
     *
     * @param mixed $value (optional) 
     * @return int
     **/
    public function count()
    {
        if (func_num_args() > 0) {
            return $this->countValue(func_get_arg(0));
        }

        return count($this->source);
    }

    /**
     * Count the occurrences of value in $this list
     *
     * @param $value
     * @return int
     **/
    public function countValue($value)
    {
        $count = 0;
        foreach ($this->source as $arrayVal) {
            if ($arrayVal === $value) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Sort the list
     *
     * @param callable $sorter (optional)
     * @return self
     **/
    public function sort(callable $sorter=null)
    {
        if (!$sorter) {
            $sorter = function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
            };
        }

        usort($this->source, $sorter);

        return $this;
    }

    /**
     * Reverse sort the list
     *
     * @return self
     **/
    public function reverse()
    {
        $this->source = array_reverse($this->source);

        return $this;
    }

    /**
     * Apply a callable to all items of this list and return
     * every result as array
     *
     * @param callable $callable
     * @return array
     **/
    public function apply(callable $callable)
    {
        return array_map($callable, $this->source);
    }

    /**
     * Filter this list by the callable $callable
     *
     * @param callable $callable
     * @return self
     **/
    public function filter(callable $callable)
    {
        $this->source = array_filter($this->source, $callable);
        return $this;
    }

    /**
     * Find a value by callback $callable. If the callback returns
     * true the index of the checked item is returned.
     * If nothing was found it a NotFoundException will be thrown
     *
     * @param callable $callable
     * @return mixed
     * @throws \Ems\Contracts\Core\NotFound
     **/
    public function find(callable $callable)
    {
        return $this->offsetGet($this->findIndex($callable));
    }

    /**
     * Find an index by callback $callable. If the callback returns
     * true the index of the checked item is returned.
     * If nothing was found it a NotFoundException will be thrown to avoid
     * checks of 0 vs false
     *
     * @param callable $callable
     * @return int
     * @throws \Ems\Contracts\Core\NotFound
     **/
    public function findIndex(callable $callable)
    {
        foreach ($this->source as $i=>$item) {
            if ($callable($item) === true) {
                return $i;
            }
        }
        throw new ItemNotFoundException('Item not found by callable');
    }

    /**
     * Allow foreach
     *
     * @return \ArrayIterator
     **/
    public function getIterator()
    {
        return new ArrayIterator($this->source);
    }

    /**
     * Check if index $offset exists (< count()-1)
     *
     * @param int $offset
     * @return bool
     **/
    public function offsetExists($offset)
    {
        return isset($this->source[$offset]);
    }

    /**
     * Get the offset $offset or throw an OutOfRangeException
     *
     * @param int $offset
     * @return mixed
     **/
    public function offsetGet($offset)
    {
        if (isset($this->source[$offset])) {
            return $this->source[$offset];
        }
        throw new OutOfRangeException("Index $offset not in list");
    }

    /**
     * Set the offset $offset
     *
     * @param int $offset
     * @param mixed $value
     * @return null
     **/
    public function offsetSet($offset, $value)
    {
        $this->insert($offset, $value);
    }

    /**
     * Remove the offset $offset
     *
     * @param int $offset
     * @return null
     **/
    public function offsetUnset($offset)
    {
        $this->pop($offset);
    }

    /**
     * Return the source array
     *
     * @return array
     **/
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set the source (array)
     * Array or \Traversable results in array_values() or items
     * an int results in range($source)
     * a string with more than one char results in str_split($source)
     * a string with one char results in range('a', $source)
     *
     * @param array|\Traversable|int|string $source (optional)
     * @return self
     **/
    public function setSource($source)
    {
        $this->source = $this->castToArray($source);
        return $this;
    }

    /**
     * Casts the passed $source to array
     *
     * @param mixed $source
     * @return array
     **/
    protected function castToArray($source)
    {
        if (is_array($source)) {
            return array_values($source);
        }

        if (is_int($source)) {
            return range(0, $source);
        }

        if (is_string($source) && strlen($source) > 1) {
            return str_split($source);
        }

        if (is_string($source) && mb_strlen($source) == 1) {
            return range(mb_strtoupper($source) == $source ? 'A' : 'a', $source);
        }

        if (!$source instanceof Traversable) {
            throw new UnexpectedValueException('Source has to be \Traversable, array, string or int');
        }

        $array = [];

        foreach ($source as $item) {
            $array[] = $item;
        }

        return $array;
    }

    /**
     * Return the first item if exists
     *
     * @return mixed
     **/
    public function first()
    {
        if (isset($this->source[0])) {
            return $this->source[0];
        }
    }

    /**
     * Return the last item
     *
     * @return mixed
     **/
    public function last()
    {
        $lastIndex = (count($this->source) - 1);
        if (isset($this->source[$lastIndex])) {
            return $this->source[$lastIndex];
        }
    }

    /**
     * Copies the list or its extended class.
     * 
     * @return self
     */
    public function copy()
    {
        return new static($this->source);
    }

    /**
     * @see OrderedList::copy()
     *
     * @return self
     */
    public function __clone()
    {
        $this->source = $this->source;
    }
}

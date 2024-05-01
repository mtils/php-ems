<?php
/**
 *  * Created by mtils on 27.07.2022 at 21:44.
 **/

namespace Ems\Core;

use ArrayAccess;
use ArrayIterator;
use Ems\Contracts\Core\Arrayable;
use Ems\Contracts\Core\ArrayData;
use Ems\Contracts\Core\HasKeys;
use Ems\Contracts\Core\Str;
use Ems\Contracts\Model\Filterable;
use Exception;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use Traversable;
use TypeError;

use function array_keys;
use function is_array;
use function is_iterable;
use function is_object;
use function is_scalar;
use function iterator_to_array;
use function method_exists;
use function print_r;


class FilterableArray implements ArrayData, Filterable, IteratorAggregate
{
    /**
     * @var ArrayAccess|array|Traversable
     */
    protected $source;

    /**
     * @var bool
     */
    protected $useFuzzySearch = true;

    public function __construct($source=[])
    {
        $this->setSource($source);
    }

    public function offsetExists($offset) : bool
    {
        return isset($this->source[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->source[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->source[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->source[$offset]);
    }

    public function toArray()
    {
        if (is_array($this->source)) {
            return $this->source;
        }
        if ($this->source instanceof Arrayable) {
            return $this->source->toArray();
        }
        return iterator_to_array($this->source);
    }

    public function clear(array $keys = null) : FilterableArray
    {
        if ($this->source instanceof ArrayData) {
            $this->source->clear($keys);
            return $this;
        }
        if ($keys === []) {
            return $this;
        }
        $keys = $keys ?: $this->sourceKeys();
        foreach ($keys as $key) {
            unset($this->source[$key]);
        }
        return $this;
    }

    public function filter($key, $value = null) : self
    {
        if ($value) {
            return $this->filter([$key=>$value]);
        }
        $matches = [];
        foreach ($this as $item) {
            foreach ($key as $criterion=>$value) {
                $itemValue = $this->getItemValue($item, $criterion);
                if (!$this->matches($itemValue, $value)) {
                    continue 2;
                }
            }
            $matches[] = $item;
        }
        return new static($matches);
    }

    /**
     * @return Iterator
     * @throws Exception
     */
    #[\ReturnTypeWillChange]
    public function getIterator() : Iterator
    {
        if ($this->source instanceof IteratorAggregate) {
            return $this->source->getIterator();
        }
        if (is_array($this->source)) {
            return new ArrayIterator($this->source);
        }
        return new IteratorIterator($this->source);
    }

    /**
     * @return bool
     */
    public function isFuzzySearchEnabled() : bool
    {
        return $this->useFuzzySearch;
    }

    /**
     * @param bool $enable
     * @return $this
     */
    public function enableFuzzySearch(bool $enable=true) : FilterableArray
    {
        $this->useFuzzySearch = $enable;
        return $this;
    }

    /**
     * @param bool $disable
     * @return $this
     */
    public function disableFuzzySearch(bool $disable=true) : FilterableArray
    {
        return $this->enableFuzzySearch(!$disable);
    }

    /**
     * @return array|ArrayAccess|Traversable
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param $source
     * @return void
     */
    public function setSource($source) : self
    {
        if (!is_array($source) && !$source instanceof ArrayAccess) {
            throw new TypeError('Source has to be array or implement ArrayAccess');
        }
        if (!is_iterable($source)) {
            throw new TypeError('Source has to be iterable (implements Traversable or array)');
        }
        $this->source = $source;
        return $this;
    }

    /**
     * @return string[]
     */
    protected function sourceKeys()
    {
        if ($this->source instanceof HasKeys) {
            return $this->source->keys();
        }
        if (is_array($this->source)) {
            return array_keys($this->source);
        }
        $keys = [];
        foreach ($this->source as $key=>$value) {
            $keys[] = $key;
        }
        return $keys;
    }

    /**
     * Check if $haystack matches $pattern.
     *
     * @param mixed $haystack
     * @param mixed $needle
     *
     * @return bool
     */
    protected function matches($haystack, $needle) : bool
    {
        $haystack = is_object($haystack) && method_exists($haystack, '__toString') ? $haystack->__toString : $haystack;

        if ($haystack === null && $needle === '') {
            return true;
        }

        if (!is_scalar($haystack) || !is_scalar($needle)) {
            return $haystack === $needle;
        }

        if ($this->useFuzzySearch) {
            return Str::match($haystack, $needle);
        }
        return $haystack == $needle;
    }

    /**
     * @param mixed $item
     * @param string $key
     * @return mixed|null
     */
    protected function getItemValue($item, string $key)
    {
        if ((is_array($item) || $item instanceof ArrayAccess) && isset($item[$key])) {
            return $item[$key];
        }
        if (is_object($item) && isset($item->$key)) {
            return $item->$key;
        }
        return null;
    }

}
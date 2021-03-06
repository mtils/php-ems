<?php

namespace Ems\Contracts\Cache;

use DateTime;

interface Storage
{
    /**
     * Make $id a valid cache key. If the value is to long you should hash it
     * instead of cutting it to avoid cache key conflicts.
     *
     * @param string $key
     *
     * @return string
     **/
    public function escape($key);

    /**
     * Return is this store has item $id.
     *
     * @param string $id
     *
     * @return bool
     **/
    public function has($id);

    /**
     * Return the item with $id.
     *
     * @param string $id
     *
     * @return mixed
     **/
    public function get($id);

    /**
     * Return all items with the passed ids. The result must be indexed by its
     * cache id if an array was passed. Every not found entry of the array will
     * NOT be added to the result array.
     * So you can detect null values by array_key_exists('key', $result).
     *
     * @param array $ids
     *
     * @return mixed
     */
    public function several(array $ids);

    /**
     * Put the $value under $id in the cache and store it until
     * $until. If $until is null store it forever.
     *
     * @param string    $id
     * @param mixed     $value
     * @param array     $tags  (optional)
     * @param \DateTime $until (optional)
     **/
    public function put($id, $value, $tags = [], DateTime $until = null);

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param mixed  $steps
     *
     * @return int|bool
     */
    public function increment($key, $steps = 1);

    /**
     * Decrement the value of an item in the cache.
     *
     * @param string $key
     * @param mixed  $steps
     *
     * @return int|bool
     */
    public function decrement($key, $steps = 1);

    /**
     * Remove all items from the cache. Return true on success.
     *
     * @return bool
     **/
    public function clear();

    /**
     * Invalidate a value by its id.
     *
     * @param $key
     *
     * @return self
     **/
    public function forget($key);

    /**
     * Invalidate all cache entries with the passed tag(s).
     *
     * @param array $tags
     *
     * @return self
     **/
    public function prune(array $tags);
}

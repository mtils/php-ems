<?php


namespace Ems\Contracts\Cache;

use Ems\Contracts\Core\Storage as BaseStorage;
use Ems\Contracts\Core\Provider;

/**
 * In opposite to the most cache implementations the
 * ems cache system has a separate logic to guess the
 * cache ids and tags. So the most methods have an optional
 * key argument
 **/
interface Cache extends BaseStorage, Provider
{

    /**
     * Turn the $value into a valid cache key.
     * If the value is scalar or null, replace any chars which are
     * not valid.
     *
     * If value is an object or array the categorizers will be asked
     * for a key
     * If the categorizers dont know a key for an array, encode the array
     * to a key
     *
     * @param mixed $value
     * @return string
     **/
    public function key($value);

    /**
     * Return if the cache has stored $key
     *
     * @param string $key
     * @return bool
     **/
    public function has($key);

    /**
     * @method mixed get(mixed $key, mixed $default=null)
     *
     * Get the key from cache, if it is not in cache
     * cache $default and return it.
     * if $default is callable call it for the result. The cache
     * then stores the result of your callable.
     * if $key is an object or array of objects guess the key.
     * If array is an array of strings return all entries, one per id
     *
     * It is obvious that from an outside call you have to have either
     * the object (and the cache was skipped) or must have the id to get it.
     * But if an object (like cacheable) just wants to save some data for itself
     * it could call Cache::get($this) to get the assoziated data
     *
     * @param string|object|array $key
     * @param mixed $default (optional)
     * @return mixed
     **/

    /**
     * Store the value under $key. Guess the key if none passed
     *
     * @param mixed $value
     * @param string $key (optional)
     * @param mixed $keySource (optional)
     * @return self
     **/
    public function add($value, $key=null, $keySource=null);

    /**
     * Pass a date or DateTime->diff() string to manipulate the
     * cache lifetime. No call to until means forever
     * Fluent interface:
     * @example Cache::until('1 day')->add()
     *
     * @param string|\DateTime $until
     * @return self
     **/
    public function until($until='+1 day');

    /**
     * Add tags to the stored data to invalidate it by tags. Fluent API:
     * @example Cache::tag('user-34', 'blog-category-12')->add($blogEntry)
     *
     * @param string|array $tags
     * @return self
     **/
    public function tag($tags);

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $steps
     * @return int|bool
     */
    public function increment($key, $steps = 1);

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $steps
     * @return int|bool
     */
    public function decrement($key, $steps = 1);

    /**
     * Invalidate a value by its id
     *
     * @param $key
     * @return self
     **/
    public function forget($key);

    /**
     * Invalidate all cache entries with the passed tag(s)
     *
     * @param array|string $tags
     * @return self
     **/
    public function prune($tags);

    /**
     * Pass a name for a different cache storage
     * @example Cache::storage('fast')->add($value)
     *
     * @param mixed $name
     * @return string
     **/
    public function storage($name);

    /**
     * Add a different store under name to use it via
     * self::store($name)
     *
     * @param string $name
     * @param \Ems\Contracts\Cache\Storage $store
     * @return self
     **/
    public function addStorage($name, Storage $store);


}

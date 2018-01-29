<?php

namespace Ems\Contracts\Cache;

use ArrayAccess;
use Ems\Contracts\Core\Provider;
use Ems\Contracts\Core\HasMethodHooks;

/**
 * In opposite to the most cache implementations the
 * ems cache system has a separate logic to guess the
 * cache ids and tags. So the using code will not be full of cache id
 * generation or lifetime, tags,.. code.
 * The HasMethodHooks interface is to ensure hooks when keys are
 * invalidated, so at first in put, forget, prune, increment,
 * decrement and purge. Listeners for read actions can be omitted
 * for performance reasons.
 **/
interface Cache extends ArrayAccess, Provider, HasMethodHooks
{
    /**
     * Turn the $value into a valid cache key.
     * If the value is scalar or null, replace any chars which are
     * not valid.
     *
     * If you pass multiple args or value is an object or array the categorizer
     * will be asked for a key.
     * If you pass multiple args the categorizer will always get an array.
     * If the categorizers dont know a key for an array, encode the array
     * to a key.
     *
     * @param mixed $value
     * @param mixed $criteria2 (optional)
     * ... (allows unlimited args)
     *
     * @return string
     **/
    public function key($value, $criteria2=null);

    /**
     * Return if the cache has stored $key.
     *
     * @param string $key
     *
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
     * @param mixed               $default (optional)
     *
     * @return mixed
     **/

    /**
     * Retrieve many values from cache. The returned array will be indexed by
     * the keys you passed to this method.
     * If a cache entry was found for a key, it will be in the returned array
     * under that key.
     * If a cache entry was not found, the key will not exist in the returned
     * array. (So you can check for null values with array_key_exists())
     *
     * @param array $keys
     *
     * @return array
     */
    public function several(array $keys);

    /**
     * Store the value under $key.
     * If no value was passed, guess the key of $keyOrValue and put
     * $keyOrValue in the cache.
     * If you pass a key but want to auto-tag and categorize the entry
     * pass the original $keySource.
     *
     * @param mixed $keyOrValue
     * @param mixed $value      (optional)
     * @param mixed $keySource  (optional)
     *
     * @return self
     **/
    public function put($keyOrValue, $value = null, $keySource = null);

    /**
     * Pass a date or DateTime->diff() string to manipulate the
     * cache lifetime. No call to until means forever
     * Fluent interface:.
     *
     * @example Cache::until('1 day')->put()
     *
     * @param string|\DateTime $until
     *
     * @return self
     **/
    public function until($until = '+1 day');

    /**
     * Add tags to the stored data to invalidate it by tags. Fluent API:.
     *
     * @example Cache::tag('user-34', 'blog-category-12')->put($blogEntry)
     *
     * @param string|array $tags
     *
     * @return self
     **/
    public function tag($tags);

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
     * Invalidate a value. Pass a scalar value and it is used as the key.
     * Pass anything other and the categorizer will be asked for a key AND tags.
     * If the categorizer finds tags ALL THESE tags get pruned after the call
     * to forget.
     *
     * @param mixed $keyOrValue
     *
     * @return self
     **/
    public function forget($keyOrValue);

    /**
     * Invalidate all cache entries with the passed tag(s).
     *
     * @param array|string $tags
     *
     * @return self
     **/
    public function prune($tags);

    /**
     * Clear the cache
     *
     * @return bool (if successfull)
     **/
    public function clear();

    /**
     * Pass a name for a different cache storage.
     *
     * @example Cache::storage('fast')->put($value)
     *
     * @param mixed $name
     *
     * @return string
     **/
    public function storage($name);

    /**
     * Add a different storage under $name to use it via
     * self::storage($name)->get($key).
     * Add a Closure to let the Closure create the storage if
     * it is requested.
     * In most cases you would add the default storage in the constructor
     * or pass an instance and all others via a Closure.
     *
     * @param string           $name
     * @param Storage|\Closure $storage
     *
     * @return self
     **/
    public function addStorage($name, $storage);

    /**
     * Return all storage names. This are all assigned names even
     * if none of em were resolved or used.
     *
     * @return Ems\Core\Collections\StringList
     **/
    public function storageNames();
}

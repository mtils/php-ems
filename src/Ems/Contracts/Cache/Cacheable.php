<?php


namespace Ems\Contracts\Cache;

/**
 * A cachable is an object which can be cached.
 * You can pass this object directly to a cache and it
 * can query itself without the need for a cache id
 **/
interface Cacheable
{
    /**
     * Determine if this item should be cached and
     * optionally its id.
     * Pass null to let the Cache\Categorizer detect its id.
     * Pass a string to determine the cache id.
     * Pass a boolean to explicit turn caching on/off
     * 
     * @param string|bool $id (optional)
     * @return self
     **/
    public function cache($id=null, $tags=[]);

    /**
     * Determine in which storage the item should be cached
     *
     * @param string $storage
     * @return self
     **/
    public function inStorage($storage);

    /**
     * Determine how long the item should be cached
     *
     * @param string|\DateTime $lifetime
     * @return self
     **/
    public function remember($lifetime);

    /**
     * Return if the object should be taken from cache
     * and saved after putting it or not
     *
     * @return bool
     **/
    public function shouldCache();

    /**
     * Return the setted cache id or none if none was setted
     *
     * @return string|null
     **/
    public function cacheId();

    /**
     * Return the setted cache storage or none if none was setted
     *
     * @return string|null
     **/
    public function cacheStorage();

    /**
     * Return the setted cache tags
     *
     * @return array
     **/
    public function cacheTags();

    /**
     * Return the setted lifetime (if setted)
     *
     * @return string|\DateTime|null
     **/
    public function lifetime();

    /**
     * Set the application cache
     *
     * @param \Ems\Contracts\Cache\Cache $cache
     * @return self
     **/
    public function setCache(Cache $cache);

}

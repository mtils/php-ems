<?php

namespace Ems\Cache\Storage;

use Ems\Contracts\Cache\Storage;
use DateTime;
use Zend_Cache;
use Zend_Cache_Core;

class Zend1CacheStorage implements Storage
{
    /**
     * @var \Zend_Cache_Core
     **/
    protected $cache;

    /**
     * @param \Zend_Cache_Core $cache
     **/
    public function __construct(Zend_Cache_Core $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return string
     **/
    public function escape($key)
    {
        $key = preg_replace('/[^a-zA-Z0-9_]/u', '_', (string) $key);

        if (strlen($key) > 255) {
            return sha1($key);
        }

        return $key;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $id
     *
     * @return bool
     **/
    public function has($id)
    {
        return $this->cache->test($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $id
     *
     * @return mixed
     **/
    public function get($id)
    {
        if (!is_array($id)) {
            return $this->cache->load($id);
        }

        $results = [];

        foreach ($id as $cacheId) {
            $results[$cacheId] = $this->cache->load($id);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     *
     * @param string    $id
     * @param mixed     $value
     * @param array     $tags  (optional)
     * @param \DateTime $until (optional)
     **/
    public function put($id, $value, $tags = [], DateTime $until = null)
    {
        return $this->cache->save($value, $id, $tags, $this->untilToLifetime($until));
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param mixed  $steps
     *
     * @return int|bool
     */
    public function increment($key, $steps = 1)
    {
        $previous = $this->cache->test($key) ? $this->cache->load($key) : 0;
        $previous += $steps;
        $this->cache->save($key, $previous);

        return $previous;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param mixed  $steps
     *
     * @return int|bool
     */
    public function decrement($key, $steps = 1)
    {
        $previous = $this->cache->test($key) ? $this->cache->load($key) : 0;
        $previous -= $steps;
        $this->cache->save($key, $previous);

        return $previous;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function clear()
    {
        return $this->cache->clean();
    }

    /**
     * {@inheritdoc}
     *
     * @param $key
     *
     * @return self
     **/
    public function forget($key)
    {
        $this->cache->remove($key);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array|string $tags
     *
     * @return self
     **/
    public function prune($tags)
    {
        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, $tags);

        return $this;
    }

    /**
     * Calculates the lifetime out of a DateTime value.
     *
     * @param \DateTime|null $until
     *
     * @return int
     **/
    protected function untilToLifetime($until)
    {
        if (!$until instanceof DateTime) {
            return self::CACHE_LIFETIME;
        }

        return $until->getTimestamp() - (new DateTime())->getTimestamp();
    }
}

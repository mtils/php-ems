<?php

namespace Ems\Cache\Storage;

use Ems\Contracts\Cache\Storage;
use DateTime;
use Zend_Cache;
use Zend_Cache_Core;

/**
 * The ArrayStorage is a temporary storage you could use in a repository
 **/
class ArrayStorage implements Storage
{
    /**
     * @var array
     **/
    protected $cache = [];

    /**
     * A second cache for faster id lookup (array_key_exists is slow)
     * and for the tags
     *
     * @var array
     **/
    protected $id2Tags = [];

    /**
     * @var array
     **/
    protected $tag2Ids = [];

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return string
     **/
    public function escape($key)
    {
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
        return isset($this->id2Tags[$id]);
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
        return $this->has($id) ? $this->cache[$id] : null;
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
        $this->forget($id);
        $this->cache[$id] = $value;
        $this->id2Tags[$id] = $tags;

        foreach ($tags as $tag) {
            if (!isset($this->tag2Ids[$tag])) {
                $this->tag2Ids[$tag] = [];
            }
            $this->tag2Ids[$tag][$id] = true;
        }
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
        $key = "integer-store::$key";

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = 0;
        }

        $this->cache[$key] += $steps;

        return $this->cache[$key];
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
        $key = "integer-store::$key";

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = 0;
        }

        $this->cache[$key] -= $steps;

        return $this->cache[$key];
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function clear()
    {
        $this->cache = [];
        $this->tag2Ids = [];
        $this->id2Tags = [];
        return true;
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
        if (!$this->has($key)) {
            return $this;
        }

        foreach ($this->id2Tags[$key] as $tag) {
            unset($this->tag2Ids[$tag][$key]);
        }

        unset($this->id2Tags[$key]);
        unset($this->cache[$key]);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $tags
     *
     * @return self
     **/
    public function prune(array $tags)
    {
        $keys = [];

        foreach ($tags as $tag) {
            if (!isset($this->tag2Ids[$tag])) {
                continue;
            }
            $keys = $keys + array_keys($this->tag2Ids[$tag]);
        }

        foreach ($keys as $key) {
            $this->forget($key);
        }

        foreach ($tags as $tag) {
            if (isset($this->tag2Ids[$tag])) {
                unset($this->tag2Ids[$tag]);
            }
        }

        return $this;
    }
}

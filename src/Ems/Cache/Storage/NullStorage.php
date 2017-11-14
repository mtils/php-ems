<?php

namespace Ems\Cache\Storage;

use Ems\Contracts\Cache\Storage;
use DateTime;
use Zend_Cache;
use Zend_Cache_Core;

/**
 * The NullStorage is to turn off caching or for unit tests
 **/
class NullStorage implements Storage
{
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
        return false;
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
        return null;
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
        return $steps;
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
        return $steps;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function clear()
    {
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
        return $this;
    }
}

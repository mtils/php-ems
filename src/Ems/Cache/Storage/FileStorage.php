<?php

namespace Ems\Cache\Storage;

use Ems\Contracts\Cache\Storage;
use DateTime;
use Zend_Cache;
use Zend_Cache_Core;

/**
 * This is a port of the zend framework 1 file cache
 **/
class FileStorage implements Storage
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
        
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function clear()
    {
        
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
        
    }

}

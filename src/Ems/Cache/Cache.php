<?php


namespace Ems\Cache;

use Ems\Contracts\Cache\Cache as CacheContract;
use Ems\Contracts\Cache\Storage;
use Ems\Contracts\Cache\Categorizer;
use Ems\Cache\Exception\CacheMissException;

class Cache implements CacheContract
{

    const DEFAULT_STORAGE = 'default';

    /**
     * @var array
     **/
    protected $storages = [];

    /**
     * @var \Ems\Contracts\Cache\Storage
     **/
    protected $storage;

    /**
     * @var \Ems\Contracts\Cache\Categorizer
     **/
    protected $categorizer;

    /**
     * @param \Ems\Contracts\Cache\Categorizer $categorizer
     **/
    public function __construct(Categorizer $categorizer)
    {
        $this->categorizer = $categorizer;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @return string
     **/
    public function key($value)
    {
        if (is_scalar($value)) {
            return $this->storage->escape((string)$value);
        }
        return $this->storage->escape($this->categorizer->key($value));
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @return bool
     **/
    public function has($key)
    {
        return $this->storage->has($key);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param mixed $default (optional)
     * @return mixed
     **/
    public function get($key, $default=null)
    {

        $isGuessedKey = (is_object($key) || $this->isCacheableArray($key));

        $cacheId = $isGuessedKey ? $this->key($key) : $key;

        // Return without checking if no default passed
        // $cacheId is string or an array of strings
        if ($default === null) {
            return $this->storage->get($cacheId);
        }

        if ($this->has($cacheId)) {
            return $this->storage->get($cacheId);
        }

        if ($default === null || $default instanceof CacheMiss) {
            return $default;
        }

        $value = is_callable($default) ? call_user_func($default) : $default;

        $isGuessedKey ? $this->add($value, $cacheId, $key) : $this->add($value, $cacheId);

        return $value;

    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @return mixed
     * @throws \Ems\Cache\Exception\CacheMissException
     **/
    public function getOrFail($id)
    {
        $value = $this->get($id, new CacheMiss);

        if ($value instanceof CacheMiss) {
            throw new CacheMissException("Cache entry not found");
        }

        return $value;

    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param string $key (optional)
     * @param mixed $keySource (optional)
     * @return self
     **/
    public function add($value, $key=null, $keySource=null)
    {

        $keySource = $keySource ?: $value;

        $key = $key ?: $this->key($keySource);
        $tags = $this->categorizer->tags($keySource);
        $lifetime = $this->categorizer->lifetime($keySource);

        $this->storage->put($key, $value, $tags, $lifetime);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\DateTime $until
     * @return self
     **/
    public function until($until='+1 day')
    {
        return $this->proxy($this, $this->storage)->with(['until'=>$until]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $tags
     * @return self
     **/
    public function tag($tags)
    {
        return $this->proxy($this, $this->storage)->with(['tag'=>$tags]);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $name
     * @return string
     **/
    public function storage($name)
    {
        return $this->proxy($this, $this->storages[$name]);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $key
     * @param  mixed  $steps
     * @return int|bool
     */
    public function increment($key, $steps = 1)
    {
        $this->storage->increment($key, $steps);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $key
     * @param  mixed  $steps
     * @return int|bool
     */
    public function decrement($key, $steps = 1)
    {
        $this->storage->decrement($key, $steps);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param $key
     * @return self
     **/
    public function forget($key)
    {
        $this->storage->forget($key);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array|string $tags
     * @return self
     **/
    public function prune($tags)
    {
        $this->storage->prune($tags);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param \Ems\Contracts\Cache\Storage $store
     * @return self
     **/
    public function addStorage($name, Storage $store)
    {

        if ($name == self::DEFAULT_STORAGE) {
            $this->storage = $store;
        }

        $this->storages[$name] = $store;

        return $this;

    }

    /**
     * {@inheritdoc}
     *
     * @see \Ems\Contracts\Core\Storage
     * @return bool (if successfull)
     **/
    public function persist()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Ems\Contracts\Core\Storage
     * @return bool (if successfull)
     **/
    public function purge()
    {
        return $this->storage->clear();
    }

    /**
     * Check if $offset exists
     *
     * @param mixed $offset
     * @return bool
     **/
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Get value of $offset
     *
     * @see \Ems\Contracts\Core\Storage
     * @param mixed $offset
     * @return mixed
     **/
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set the value of $offset
     *
     * @see \Ems\Contracts\Core\Storage
     * @param mixed $offset
     * @param mixed $value
     * @return null
     **/
    public function offsetSet($offset, $value)
    {
        $this->add($value, $offset);
    }

    /**
     * Unset $offset
     *
     * @see \Ems\Contracts\Core\Storage
     * @param mixed $offset
     * @return null
     **/
    public function offsetUnset($offset)
    {
        $this->forget($offset);
    }

    /**
     * Create a new proxy for a different storage
     *
     * @param self $parent
     * @param \Ems\Contracts\Cache\Storage $storage
     * @return \Ems\Cache\CacheProxy
     **/
    protected function proxy($parent, Storage $storage)
    {
        return new CacheProxy($parent, $storage, $this->categorizer);
    }

    /**
     * Return if the value is a cacheable array
     *
     * @param mixed $value
     * @return bool
     **/
    protected function isCacheableArray($value)
    {
        return (is_array($value) && !$this->isArrayOfStrings($value));
    }

    /**
     * @param mixed $value
     * @return bool
     **/
    protected function isArrayOfStrings($value)
    {
        if (!is_array($value)) {
            return false;
        }

        if (isset($value[0]) && (is_string($value[0]) || method_exists($value[0], '__toString') )) {
            return true;
        }

        return false;
    }

}

class CacheMiss{}

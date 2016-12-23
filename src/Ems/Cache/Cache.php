<?php

namespace Ems\Cache;

use Ems\Contracts\Cache\Cache as CacheContract;
use Ems\Contracts\Cache\Storage;
use Ems\Contracts\Cache\Categorizer;
use Ems\Cache\Exception\CacheMissException;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Exceptions\HandlerNotFoundException;

class Cache implements CacheContract
{
    use HookableTrait;

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
     *
     * @return string
     **/
    public function key($value)
    {
        if (is_scalar($value)) {
            return $this->storage->escape((string) $value);
        }

        return $this->storage->escape($this->categorizer->key($value));
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
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
     * @param mixed  $default (optional)
     *
     * @return mixed
     **/
    public function get($key, $default = null)
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

        if ($default === null || $default instanceof _CacheMiss) {
            return $default;
        }

        $value = is_callable($default) ? call_user_func($default) : $default;

        $isGuessedKey ? $this->put($cacheId, $value, $key) : $this->put($cacheId, $value);

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @throws CacheMissException
     *
     * @return mixed
     **/
    public function getOrFail($id)
    {
        $value = $this->get($id, new _CacheMiss());

        if ($value instanceof _CacheMiss) {
            throw new CacheMissException('Cache entry not found');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $keyOrValue
     * @param mixed $value      (optional)
     * @param mixed $keySource  (optional)
     *
     * @return self
     **/
    public function put($keyOrValue, $value = null, $keySource = null)
    {
        $key = $value === null ? null : $keyOrValue;
        $value = $value === null ? $keyOrValue : $value;

        $keySource = $keySource ?: $value;

        $key = $key ?: $this->key($keySource);
        $tags = $this->categorizer->tags($keySource);
        $lifetime = $this->categorizer->lifetime($keySource);

        $this->callBeforeListeners('put', [$key, $value, $tags, $lifetime]);
        $this->storage->put($key, $value, $tags, $lifetime);
        $this->callAfterListeners('put', [$key, $value, $tags, $lifetime]);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\DateTime $until
     *
     * @return self
     **/
    public function until($until = '+1 day')
    {
        return $this->proxy($this, $this->storage)->with(['until' => $until]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $tags
     *
     * @return self
     **/
    public function tag($tags)
    {
        return $this->proxy($this, $this->storage)->with(['tag' => $tags]);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $name
     *
     * @return string
     **/
    public function storage($name)
    {
        return $this->proxy($this, $this->storages[$name]);
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
        $this->callBeforeListeners('increment', [$key, $steps]);
        $this->storage->increment($key, $steps);
        $this->callAfterListeners('increment', [$key, $steps]);

        return $this;
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
        $this->callBeforeListeners('decrement', [$key, $steps]);
        $this->storage->decrement($key, $steps);
        $this->callAfterListeners('decrement', [$key, $steps]);

        return $this;
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
        $this->callBeforeListeners('forget', [$key]);
        $this->storage->forget($key);
        $this->callAfterListeners('forget', [$key]);

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
        $this->callBeforeListeners('prune', [$tags]);
        $this->storage->prune($tags);
        $this->callAfterListeners('prune', [$tags]);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string  $name
     * @param Storage $store
     *
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
     * @param string $name (optional)
     *
     * @throws \Ems\Contracts\NotFound
     *
     * @return Storage
     **/
    public function getStorage($name=null)
    {
        $name = $name ?: self::DEFAULT_STORAGE;

        if (isset($this->storages[$name])) {
            return $this->storages[$name];
        }

        throw new HandlerNotFoundException("No Storage saved under $name");
    }

    /**
     * {@inheritdoc}
     *
     * @see \Ems\Contracts\Core\Storage
     *
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
     *
     * @return bool (if successfull)
     **/
    public function purge()
    {
        $this->callBeforeListeners('purge');
        $result = $this->storage->clear();
        $this->callAfterListeners('purge');

        return $result;
    }

    /**
     * Check if $offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     **/
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Get value of $offset.
     *
     * @see \Ems\Contracts\Core\Storage
     *
     * @param mixed $offset
     *
     * @return mixed
     **/
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set the value of $offset.
     *
     * @see \Ems\Contracts\Core\Storage
     *
     * @param mixed $offset
     * @param mixed $value
     **/
    public function offsetSet($offset, $value)
    {
        $this->put($offset, $value);
    }

    /**
     * Unset $offset.
     *
     * @see \Ems\Contracts\Core\Storage
     *
     * @param mixed $offset
     **/
    public function offsetUnset($offset)
    {
        $this->forget($offset);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function methodHooks()
    {
        return ['put', 'increment', 'decrement', 'forget', 'prune', 'persist'];
    }

    /**
     * Create a new proxy for a different storage.
     *
     * @param self    $parent
     * @param Storage $storage
     *
     * @return CacheProxy
     **/
    protected function proxy($parent, Storage $storage)
    {
        return new CacheProxy($parent, $storage, $this->categorizer);
    }

    /**
     * Return if the value is a cacheable array.
     *
     * @param mixed $value
     *
     * @return bool
     **/
    protected function isCacheableArray($value)
    {
        return is_array($value) && !$this->isArrayOfStrings($value);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     **/
    protected function isArrayOfStrings($value)
    {
        if (!is_array($value)) {
            return false;
        }

        if (isset($value[0]) && (is_string($value[0]) || method_exists($value[0], '__toString'))) {
            return true;
        }

        return false;
    }
}

class _CacheMiss
{
}

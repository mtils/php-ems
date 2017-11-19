<?php

namespace Ems\Cache;

use Closure;
use Ems\Contracts\Cache\Cache as CacheContract;
use Ems\Contracts\Cache\Storage;
use Ems\Contracts\Cache\Categorizer;
use Ems\Cache\Exception\CacheMissException;
use Ems\Contracts\Core\None;
use Ems\Core\Collections\StringList;
use Ems\Core\Helper;
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
     * @var array
     **/
    protected $bindings = [];

    /**
     * @var Storage
     **/
    protected $storage;

    /**
     * @var Categorizer
     **/
    protected $categorizer;

    /**
     * @var string
     **/
    protected $storageName;

    /**
     * @param Categorizer $categorizer
     * @param Storage     $storage (optional)
     **/
    public function __construct(Categorizer $categorizer)
    {
        $this->categorizer = $categorizer;
        $this->storageName = self::DEFAULT_STORAGE;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param mixed $criteria2 (optional)
     * ... (allows unlimited args)
     *
     * @return string
     **/
    public function key($value, $criteria2=null)
    {
        $value = func_num_args() === 1 ? $value : func_get_args();

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
        $isGuessableKey = (is_object($key) || is_array($key));

        $cacheId = $isGuessableKey ? $this->key($key) : $key;

        // Return without checking if no default passed
        // $cacheId is string or an array of strings
        if ($default === null) {
            return $this->storage->get($cacheId);
        }

        if ($this->has($cacheId)) {
            return $this->storage->get($cacheId);
        }

        if ($default === null || $default instanceof None) {
            return $default;
        }

        $value = is_callable($default) ? call_user_func($default) : $default;

        $isGuessableKey ? $this->put($cacheId, $value, $key) : $this->put($cacheId, $value);

        return $value;
    }

    /**
     * @inheritdoc
     *
     * @param array $keys
     *
     * @return array
     */
    public function several(array $keys)
    {
        return $this->storage->several($keys);
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
        $value = $this->get($id, new None());

        if ($value instanceof None) {
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

        $this->callBeforeListeners('put', [$this->storageName, $key, $value, $tags, $lifetime]);
        $this->storage->put($key, $value, $tags, $lifetime);
        $this->callAfterListeners('put', [$this->storageName, $key, $value, $tags, $lifetime]);

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
        return $this->proxy($this, $this->getStorage($name), $name);
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
        $this->callBeforeListeners('increment', [$this->storageName, $key, $steps]);
        $this->storage->increment($key, $steps);
        $this->callAfterListeners('increment', [$this->storageName, $key, $steps]);

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
        $this->callBeforeListeners('decrement', [$this->storageName, $key, $steps]);
        $this->storage->decrement($key, $steps);
        $this->callAfterListeners('decrement', [$this->storageName, $key, $steps]);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $keyOrValue
     *
     * @return self
     **/
    public function forget($keyOrValue)
    {

        $key = is_scalar($keyOrValue) ? $keyOrValue : null;

        $tags = [];

        if (!$key) {
            $key = $this->categorizer->key($keyOrValue);
            $tags = $this->categorizer->tags($keyOrValue);
        }

        if (!$key) {
            throw new HandlerNotFoundException("No categorizer found a key for " . Helper::typeName($keyOrValue));
        }

        $this->callBeforeListeners('forget', [$this->storageName, $key, $tags]);
        $this->storage->forget($this->storage->escape($key));

        if ($tags) {
            $this->prune($tags);
        }

        $this->callAfterListeners('forget', [$this->storageName, $key, $tags]);

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
        $tags = is_array($tags) ? $tags : func_get_args();
        $this->callBeforeListeners('prune', [$this->storageName, $tags]);
        $this->storage->prune($tags);
        $this->callAfterListeners('prune', [$this->storageName, $tags]);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string          $name
     * @param Storage|Closure $storage
     *
     * @return self
     **/
    public function addStorage($name, $storage)
    {

        $isClosure = $storage instanceof Closure;

        if ($name == self::DEFAULT_STORAGE) {
            $this->storage = $isClosure ? $storage($name) : $storage;
            $this->storages[$name] = $this->storage;
            return $this;
        }

        if ($isClosure) {
            $this->bindings[$name] = $storage;
            return $this;
        }

        $this->storages[$name] = $storage;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return StringList
     **/
    public function storageNames()
    {
        $names = [];

        foreach ($this->storages as $name=>$storage) {
            $names[$name] = true;
        }

        foreach ($this->bindings as $name=>$binding) {
            $names[$name] = true;
        }

        return new StringList(array_keys($names));
    }

    /**
     * {@inheritdoc}
     *
     *
     * @return bool (if successfull)
     **/
    public function clear()
    {
        $this->callBeforeListeners('clear', [$this->storageName]);
        $result = $this->storage->clear();
        $this->callAfterListeners('clear', [$this->storageName]);

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
     * @see Storage
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
        return ['put', 'increment', 'decrement', 'forget', 'prune', 'clear'];
    }

    /**
     * Create a new proxy for a different storage.
     *
     * @param self    $parent
     * @param Storage $storage
     * @param string  $storageName (optional)
     *
     * @return CacheProxy
     **/
    protected function proxy($parent, Storage $storage, $storageName=self::DEFAULT_STORAGE)
    {
        return new CacheProxy(
            $parent,
            $this->categorizer,
            $storage,
            $storageName
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name (optional)
     *
     * @throws \Ems\Contracts\Errors\NotFound
     *
     * @return Storage
     **/
    protected function getStorage($name=null)
    {
        $name = $name ?: self::DEFAULT_STORAGE;

        if (isset($this->storages[$name])) {
            return $this->storages[$name];
        }

        if (isset($this->bindings[$name])) {
            $this->storages[$name] = call_user_func($this->bindings[$name], $name);
            return $this->storages[$name];
        }

        throw new HandlerNotFoundException("No Storage saved under $name");
    }

}
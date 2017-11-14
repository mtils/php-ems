<?php

namespace Ems\Cache;

use Ems\Contracts\Cache\Cache as CacheContract;
use Ems\Contracts\Cache\Storage;
use Ems\Contracts\Cache\Categorizer;
use DateTime;

class CacheProxy extends Cache implements CacheContract
{
    /**
     * @var \Ems\Cache\Cache
     **/
    protected $parent;

    /**
     * @var \DateTime
     **/
    protected $until;

    /**
     * @var array
     **/
    protected $tags = [];

    /**
     * @param Cache       $parent
     * @param Categorizer $categorizer
     * @param Storage     $storage
     * @param string      $storageName
     **/
    public function __construct(Cache $parent, Categorizer $categorizer, Storage $storage, $storageName)
    {
        $this->parent = $parent;
        $this->categorizer = $categorizer;
        $this->storage = $storage;
        $this->storageName = $storageName;
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

        $key = $key ?: $this->categorizer->key($keySource);

        $tags = $this->tags ? $this->tags : $this->categorizer->tags($keySource);

        $lifetime = $this->until ? $this->until : $this->categorizer->lifetime($keySource);

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
        return $this->proxy($this->parent, $this->storage, $this->storageName)
                    ->with($this->attributes(['until' => $until]));
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
        return $this->proxy($this->parent, $this->storage, $this->storageName)
                    ->with($this->attributes(['tag' => $tags]));
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
        return $this->parent->storage($name)
                    ->with($this->attributes());
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
        $this->parent->addStorage($name, $storage);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object   $event
     * @param callable        $listener
     *
     * @return self
     **/
    public function onBefore($event, callable $listener)
    {
        $this->parent->onBefore($event, $listener);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object   $event
     * @param callable        $listener
     *
     * @return self
     **/
    public function onAfter($event, callable $listener)
    {
        $this->parent->onAfter($event, $listener);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object $event
     * @param string $position ('after'|'before'|'')
     *
     * @return array
     **/
    public function getListeners($event, $position = '')
    {
        return $this->parent->getListeners($event, $position);// call_user_func($this->listenerProvider, $event, $position);
    }

    /**
     * {@inheritdoc}
     *
     * @return Ems\Core\Collections\StringList
     **/
    public function storageNames()
    {
        return $this->parent->storageNames();
    }

    public function with(array $attributes)
    {
        if (isset($attributes['until'])) {
            $this->setUntil($attributes['until']);
        }

        if (isset($attributes['tag'])) {
            $this->tags = (array) $attributes['tag'];
        }

        return $this;
    }

    /**
     * Set inline until
     *
     * @param string|DateTime $until
     **/
    protected function setUntil($until)
    {
        if ($until instanceof DateTime) {
            $this->until = $until;

            return;
        }

        $this->until = (new DateTime())->modify('+'.ltrim($until, '+'));
    }

    /**
     * Build attributes for a fork
     *
     * @param array $overwrite
     *
     * @return array
     **/
    protected function attributes(array $overwrite = [])
    {
        return array_merge(['until' => $this->until, 'tag' => $this->tags], $overwrite);
    }
}

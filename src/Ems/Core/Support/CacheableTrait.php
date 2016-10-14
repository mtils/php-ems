<?php


namespace Ems\Core\Support;

use Ems\Contracts\Cache\Cache;

trait CacheableTrait
{

    /**
     * @var bool
     **/
    protected $_shouldCache = true;

    /**
     * @var string
     **/
    protected $_cacheId = '';

    /**
     * @var array
     **/
    protected $_cacheTags = [];

    /**
     * @var string|\DateTime|null
     **/
    protected $_lifetime = null;

    /**
     * @var string
     **/
    protected $_cacheStorage = 'default';

    /**
     * @var \Ems\Contracts\Cache\Cache
     **/
    protected $_cache;

    /**
     * {@inheritdoc}
     * 
     * @param string|bool $id (optional)
     * @return self
     **/
    public function cache($id=null, $tags=[])
    {
        $this->_shouldCache = $id === false ? false : true;
        $this->_cacheId = is_string($id) ? $id : $this->_cacheId;
        $this->_cacheTags = $tags ? (array)$tags : $this->_cacheTags;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $storage
     * @return self
     **/
    public function inStorage($storage)
    {
        $this->_cacheStorage = $storage;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\DateTime $lifetime
     * @return self
     **/
    public function remember($lifetime)
    {
        $this->_lifetime = $lifetime;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function shouldCache()
    {
        return $this->_shouldCache;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     **/
    public function cacheId()
    {
        return $this->_cacheId;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     **/
    public function cacheStorage()
    {
        return $this->_cacheStorage;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function cacheTags()
    {
        return $this->_cacheTags;
    }

    /**
     * Return the setted lifetime (if setted)
     *
     * @return string|\DateTime|null
     **/
    public function lifetime()
    {
        return $this->_lifetime;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Cache\Cache $cache
     * @return self
     **/
    public function setCache(Cache $cache)
    {
        $this->_cache = $cache;
        return $this;
    }
}

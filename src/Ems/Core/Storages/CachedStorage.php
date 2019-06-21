<?php
/**
 *  * Created by mtils on 13.06.19 at 12:01.
 **/

namespace Ems\Core\Storages;


use ArrayAccess;
use Ems\Contracts\Core\Storage;
use Ems\Contracts\Core\Type;

/**
 * Class CachedStorage
 *
 * This is a proxy storage that caches all read access and syncs the
 * write operations.
 * Use it standalone and it uses an array as cache. Set another object with
 * ArrayAccess interface as cache to use a different cache.
 * (Storage extends ArrayInterface)
 *
 * @package Ems\Core\Storages
 */
class CachedStorage extends AbstractProxyStorage
{
    /**
     * @var array|ArrayAccess
     */
    protected $cache;

    public function __construct(Storage $storage, $cache=[])
    {
        parent::__construct($storage);
        $this->setCache($cache);
    }

    /**
     * Return if the key $key does exist. At the end if the file exists.
     *
     * @param string $offset
     *
     * @return bool
     **/
    public function offsetExists($offset)
    {
        if (isset($this->cache[$offset])) {
            return true;
        }

        return parent::offsetExists($offset);
    }



    /**
     * @return array|ArrayAccess
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param array|ArrayAccess $cache
     *
     * @return CachedStorage
     */
    public function setCache($cache)
    {
        $this->cache = Type::forceAndReturn($cache, ArrayAccess::class);
        return $this;
    }


}
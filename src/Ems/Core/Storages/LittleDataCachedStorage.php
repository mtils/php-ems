<?php
/**
 *  * Created by mtils on 13.06.19 at 13:07.
 **/

namespace Ems\Core\Storages;


use Ems\Core\Collections\StringList;
use Ems\Core\Exceptions\KeyNotFoundException;

/**
 * Class LittleDataCachedStorage
 *
 * This is cache storage for small data storage. If you know that it is the most
 * effective way to just read the whole data and cache it instead of caching
 * single entries use this one.
 *
 * @package Ems\Core\Storages
 */
class LittleDataCachedStorage extends AbstractProxyStorage
{
    /**
     * @var array
     */
    protected $cache;

    /**
     * Return if the key $key does exist. At the end if the file exists.
     *
     * @param string $offset
     *
     * @return bool
     **/
    public function offsetExists($offset)
    {
        $all = $this->toArray();
        return isset($all[$offset]);
    }

    /**
     * Return the data of $offset. No error handling is done here. You have to
     * catch the filesystem exceptions by yourself.
     *
     * @param string $offset
     *
     * @return mixed
     **/
    public function offsetGet($offset)
    {
        $all = $this->toArray();
        if (isset($all[$offset])) {
            return $all[$offset];
        }
        throw new KeyNotFoundException("Key $offset not found");
    }

    /**
     * Put data into this storage. At least write a file.
     *
     * @param string $offset
     * @param mixed $value
     *
     * @return void
     **/
    public function offsetSet($offset, $value)
    {
        parent::offsetSet($offset,$value);
        $this->invalidateCache();
    }

    /**
     * Unset $offset. If the file or the directory does not exist, just ignore
     * the error
     *
     * @param string $offset
     *
     * @return void
     **/
    public function offsetUnset($offset)
    {
        parent::offsetUnset($offset);
        $this->invalidateCache();
    }

    /**
     * {@inheritdoc}
     *
     * @return StringList
     **/
    public function keys()
    {
        return new StringList(array_keys($this->toArray()));
    }

    /**
     * @inheritDoc
     */
    public function purge(array $keys = null)
    {
        $result = parent::purge($keys);
        $this->invalidateCache();
        return $result;
    }

    /**
     * @param array $keys (optional)
     *
     * @return self
     **/
    public function clear(array $keys = null)
    {
        parent::clear($keys);
        $this->invalidateCache();
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * CAUTION: Be careful with this method! You will perhaps end up in filling
     * your whole memory with this.
     *
     * @return array
     **/
    public function toArray()
    {
        if ($this->cache === null) {
            $this->cache = parent::toArray();
        }
        return $this->cache;
    }

    /**
     * Delete the cache
     */
    protected function invalidateCache()
    {
        $this->cache = null;
    }

}
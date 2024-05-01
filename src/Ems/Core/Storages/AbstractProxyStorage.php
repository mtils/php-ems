<?php
/**
 *  * Created by mtils on 09.09.18 at 10:30.
 **/

namespace Ems\Core\Storages;


use Ems\Contracts\Core\HasKeys;
use Ems\Contracts\Core\Storage;
use Ems\Core\Collections\StringList;

abstract class AbstractProxyStorage implements Storage, HasKeys
{
    /**
     * @var Storage
     **/
    protected $storage;

    /**
     * @param Storage $storage
     **/
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Return if the key $key does exist. At the end if the file exists.
     *
     * @param string $offset
     *
     * @return bool
     **/
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->storage->offsetExists($offset);
    }

    /**
     * Return the data of $offset. No error handling is done here. You have to
     * catch the filesystem exceptions by yourself.
     *
     * @param string $offset
     *
     * @return mixed
     **/
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->storage->offsetGet($offset);
    }

    /**
     * Put data into this storage. At least write a file.
     *
     * @param string $offset
     * @param mixed  $value
     *
     * @return void
     **/
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->storage->offsetSet($offset, $value);
    }

    /**
     * Unset $offset. If the file or the directory does not exist, just ignore
     * the error
     *
     * @param string $offset
     *
     * @return void
     **/
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->storage->offsetUnset($offset);
    }

    /**
     * @param array $keys (optional)
     *
     * @return self
     **/
    public function clear(array $keys=null)
    {
        $this->storage->clear($keys);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return StringList
     **/
    public function keys()
    {
        return $this->storage->keys();
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
        return $this->storage->toArray();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function storageType()
    {
        return self::UTILITY;
    }

    /**
     * @inheritDoc
     */
    public function isBuffered()
    {
        return $this->storage->isBuffered();
    }

    /**
     * @inheritDoc
     */
    public function persist()
    {
        return $this->storage->persist();
    }

    /**
     * @inheritDoc
     */
    public function purge(array $keys = null)
    {
        return $this->storage->purge($keys);
    }


}
<?php

namespace Ems\Core\Storages;


use Ems\Contracts\Core\UnbufferedStorage;
use Ems\Contracts\Core\BufferedStorage;

/**
 * The UnbufferedStorageProxy makes a buffered storage an unbuffered.
 * Just wrap the other one with this proxy and all changes get instantly
 * persisted.
 **/
class UnbufferedStorageProxy implements UnbufferedStorage
{

    /**
     * @var BufferedStorage
     **/
    protected $storage;

    /**
     * @param BufferedStorage $storage
     **/
    public function __construct(BufferedStorage $storage)
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
    public function offsetGet($offset)
    {
        return $this->storage->offsetGet($offset);
    }

    /**
     * Put data into this storage. At least write a file.
     *
     * @param string $offset
     * @param mixed  $value
     **/
    public function offsetSet($offset, $value)
    {
        $this->storage->offsetSet($offset, $value);
        $this->storage->persist();
    }

    /**
     * Unset $offset. If the file or the directory does not exist, just ignore
     * the error
     *
     * @param string $offset
     **/
    public function offsetUnset($offset)
    {
        $this->storage->purge([$offset]);
    }

    /**
     * @param array $keys (optional)
     *
     * @return self
     **/
    public function clear(array $keys=null)
    {
        $this->storage->purge($keys);
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

}

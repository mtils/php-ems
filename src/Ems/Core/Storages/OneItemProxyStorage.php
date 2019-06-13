<?php
/**
 *  * Created by mtils on 11.06.19 at 11:01.
 **/

namespace Ems\Core\Storages;

use function array_keys;
use function count;
use Ems\Contracts\Core\PushableStorage;
use Ems\Contracts\Core\Type;
use Ems\Core\Collections\StringList;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Contracts\Core\Storage;

/**
 * Class OneItemProxyStorage
 *
 * This storage uses all results of a parent storage as one storage.
 * This means it deserialize a parent storage completely with toArray(),
 * takes the first (and only) entry and serialized all its own data into it.
 *
 * Why would someone needs something like this?
 * If you already have a PushableStorage you do not know the keys that it
 * will create.
 * So this class just use the whole storage and assures that it creates just
 * one entry with the complete data of this storage.
 *
 * Especially if you have SQL Storage with a discriminator. Then there is one
 * entry for all entries of one discriminator and you have something like a
 * namespace.
 *
 * @package Ems\Core\Storages
 */
class OneItemProxyStorage extends AbstractProxyStorage
{
    /**
     * @var string
     */
    protected $fictitiousId='';

    public function __construct(Storage $storage)
    {
        parent::__construct($storage);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     *
     * @return bool
     **/
    public function offsetExists($offset)
    {
        $data = $this->toArray();
        return isset($data[$offset]);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     *
     * @return mixed
     **/
    public function offsetGet($offset)
    {
        $data = $this->toArray();
        if (!isset($data[$offset])) {
            throw new KeyNotFoundException("Key '$offset' not found.");
        }
        return $data[$offset];
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     * @param mixed $value
     *
     * @return void
     **/
    public function offsetSet($offset, $value)
    {
        if($storedData = $this->readAll()) {
            $key = $this->guessKey($storedData);
            $data = $storedData[$key];
            $data[$offset] = $value;
            $this->storage[$key] = $data;
            return;
        }

        if ($this->storage instanceof PushableStorage) {
            $this->storage->offsetPush([$offset => $value]);
            return;
        }

        $this->storage[$this->getFictitiousId()] = [$offset => $value];
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     *
     * @return void
     **/
    public function offsetUnset($offset)
    {
        if(!$storedData = $this->readAll()) {
            return;
        }

        $key = $this->guessKey($storedData);
        $data = $storedData[$key];

        if (!isset($data[$offset])) {
            throw new KeyNotFoundException("Key '$offset' not found.");
        }

        unset($data[$offset]);

        $this->storage[$key] = $data;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool (if successful)
     **/
    public function purge(array $keys = null)
    {
        if ($keys === []) {
            return false;
        }

        if(!$storedData = $this->readAll()) {
            return false;
        }

        $idKey = $this->guessKey($storedData);

        if ($keys === null) {
            $this->storage->offsetUnset($idKey);
            return true; // Just guess we did something...
        }

        $data = $storedData[$idKey];

        $hit = false;
        foreach($keys as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
                $hit = true;
            }
        }
        $this->storage->offsetSet($idKey, $data);
        return $hit;
    }

    /**
     * @param array $keys (optional)
     *
     * @return self
     **/
    public function clear(array $keys = null)
    {
        $this->purge($keys);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return StringList
     **/
    public function keys()
    {
        if (!$data = $this->toArray()) {
            return new StringList([]);
        }

        return new StringList(array_keys($data));
    }

    /**
     * {@inheritdoc}
     *
     *
     * @return array
     **/
    public function toArray()
    {
        $allEntries = $this->readAll();
        if (!count($allEntries)) {
            return [];
        }
        return $allEntries[$this->guessKey($allEntries)];
    }


    /**
     * Get the pseudo ID for the one entry.
     *
     * @return string
     */
    public function getFictitiousId()
    {
        if (!$this->fictitiousId) {
            $this->fictitiousId = Type::snake_case(Type::short(static::class), '-');
        }
        return $this->fictitiousId;
    }

    /**
     * Set the pseudo id for the one entry. Only makes sense if the storage is
     * no PushableStorage.
     *
     * @param string $fictitiousId
     *
     * @return OneItemProxyStorage
     */
    public function setFictitiousId($fictitiousId)
    {
        $this->fictitiousId = $fictitiousId;
        return $this;
    }

    /**
     * @return array
     */
    protected function readAll()
    {
        if (!$allEntries = $this->storage->toArray()) { // All entries are only one!
            return [];
        }
        return $allEntries;
    }

    /**
     * Guess the id/key of the single entry.
     *
     * @param array $allEntries
     *
     * @return int|string
     */
    protected function guessKey(array &$allEntries)
    {
        return $allEntries ? array_keys($allEntries)[0] : '';
    }
}
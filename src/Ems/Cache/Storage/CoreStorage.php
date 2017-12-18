<?php

namespace Ems\Cache\Storage;

use Ems\Contracts\Cache\Storage as CacheStorage;
use Ems\Contracts\Core\Subscribable;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Storage as CoreStorageContract;
use Ems\Contracts\Core\BufferedStorage;
use Ems\Contracts\Core\UnbufferedStorage;
use Ems\Contracts\Model\QueryableStorage;
use Ems\Core\Exceptions\DataIntegrityException;
use Ems\Core\Exceptions\MisConfiguredException;
use Ems\Core\Storages\UnbufferedStorageProxy;
use Ems\Core\Patterns\SubscribableTrait;
use Ems\Core\Serializer;
use DateTime;
use Ems\Contracts\Core\Type;
use UnexpectedValueException;

/**
 * The CoreStorage is an Cache Backend using the Ems\Contracts\Core\Storage
 * classes to store data.
 * For tagging it needs a queryable storage (like SQLStorage). Otherwise it
 * had to iterate over all results to find the tags.
 *
 * This class does the serializing of everything what is not a resource
 * (gettype($var) == 'resource') by itself. So if you add a storage, it should
 * not serialize the data. (You can use the BlobSerializer inside the added
 * storage).
 *
 * If the added Queryable Storage cant take big values (or shouldnt like SQLStorage)
 * you can add a separate CoreStorage (which not has to be queryable) to store
 * the big values.
 * This class will that manage by a threshhold when to store the data in the
 * bigStorage and when in the Queryable Storage.
 *
 * Values of type resource (gettype($var) == 'resource') will always be passed
 * unaltered to the bigStorage. So you can build an image cache or whatever.
 *
 * If there are some inconsistencies in this storage it will not throw exceptions.
 * You can assign a callable via the Subscribable Interface ($storage->on('error', $listener)
 * to get informed and throw it by your own.
 **/
class CoreStorage implements CacheStorage, Subscribable
{
    use SubscribableTrait;

    /**
     * @var QueryableStorage
     **/
    protected $storage;

    /**
     * If the main storage is buffered, it will add a proxy for all write
     * operations here.
     *
     * @var UnBufferedStorage
     **/
    protected $writeStorage;

    /**
     * @var CoreStorageContract
     **/
    protected $bigStorage;

    /**
     * @var SerializerContract
     **/
    protected $serializer;

    /**
     * @var array
     **/
    protected $entryCache = [];

    /**
     * The treshhold when to put bytes into the bigStorage.
     *
     * @see https://www.sqlite.org/fasterthanfs.html
     *
     * @var int
     **/
    protected $maxMainStorageBytes = 256000;

    /**
     * This is an empty cache entry.
     * Entries are marked if they were saved in bigStorage (outside=1),
     * of they were serialized (plain=0).
     * The tags are stored by a |$tagName| syntax in tags.
     * The lifetime is stored in "valid_until" as a unix timestamp.
     *
     * @var array
     **/
    protected $entryTemplate = [
        'payload'     => null,
        'outside'     => 0,
        'plain'       => 0, // resources are serialized by bigStorage
        'tags'        => '',
        'valid_until' => 0
    ];

    /**
     * @param QueryableStorage    $storage
     * @param UnBufferedStorage $bigStorage (optional)
     * @param SerializerContract  $serializer (optional)
     **/
    public function __construct(QueryableStorage $storage,
                                UnbufferedStorage $bigStorage=null,
                                SerializerContract $serializer=null)
    {
        $this->storage = $storage;
        $this->bigStorage = $bigStorage;
        $this->serializer = $serializer ?: new Serializer;
        $this->writeStorage = $storage instanceof BufferedStorage ?
                              new UnbufferedStorageProxy($storage) : $storage;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return string
     **/
    public function escape($key)
    {
        $key = preg_replace('/[^a-zA-Z0-9_]/u', '_', (string) $key);

        if (strlen($key) > 255) {
            return sha1($key);
        }

        return $key;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $id
     *
     * @return bool
     **/
    public function has($id)
    {
        $entry = $this->getEntry($id);
        return !$this->isMiss($entry);

    }

    /**
     * {@inheritdoc}
     *
     * @param string $id
     *
     * @return mixed
     **/
    public function get($id)
    {
        $entry = $this->getEntry($id);
        return $this->isMiss($entry) ? null : $this->getPayload($id, $entry);
    }

    /**
     * @inheritdoc
     *
     * @param array $ids
     *
     * @return mixed
     */
    public function several(array $ids)
    {
        $results = [];
        foreach ($this->getEntry($ids) as $cacheId=>$entry) {
            if (!$this->isExpired($entry)) {
                $results[$cacheId] =  $this->getPayload($cacheId, $entry);
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     *
     * @param string    $id
     * @param mixed     $value
     * @param array     $tags  (optional)
     * @param \DateTime $until (optional)
     **/
    public function put($id, $value, $tags = [], DateTime $until = null)
    {

        $entry = $this->entryTemplate;

        $canSerialize = $this->canSerialize($value);
        $shouldSerialize = $this->shouldSerialize($value);
        $isPlain = true;

        if (!$canSerialize && !$this->bigStorage) {
            throw new UnexpectedValueException('Cannot store a value of type ' . Type::of($value) . '. Assign a big storage who supports that.');
        }

        // Lets save some memory and try not to copy value more around than
        // necessary

        $plainValue = null;

        if ($shouldSerialize) {
            $plainValue = $value;
            $value = $this->serializer->serialize($value);
            $isPlain = false;
        }

        // $value is now string || resource
        $writtenOutside = $this->writeToBigStorageIfNeeded($id, $value);

        $validUntil = $until ? $until->getTimestamp() : 0;

        $entry['plain'] = (int)$isPlain;
        $entry['outside'] = (int)$writtenOutside;
        $entry['payload'] = $writtenOutside ? '' : $value;
        $entry['tags'] = $tags ? ('|' . implode('|', $tags) . '|') : '';
        $entry['valid_until'] = $until ? $until->getTimestamp() : 0;


        $this->writeStorage[$id] = $entry;

        // Write the unserialized value back to put it into the memory cache
        if (!$isPlain && !$writtenOutside) {
            $entry['plain'] = 1;
            $entry['payload'] = $plainValue;
        }

        $this->entryCache[$id] = $entry;

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
        $previous = $this->has($key) ? $this->get($key) : 0;
        $previous += $steps;
        $this->put($key, $previous);

        return $previous;
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
        $previous = $this->has($key) ? $this->get($key) : 0;
        $previous -= $steps;
        $this->put($key, $previous);

        return $previous;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function clear()
    {
        $purged = $this->writeStorage->clear();

        if ($this->bigStorage) {
            $this->bigStorage->clear();
        }

        $this->entryCache = [];

        return $purged;
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
        unset($this->writeStorage[$key]);

        if ($this->bigStorage) {
            unset($this->bigStorage[$key]);
        }

        $this->entryCache[$key] = $this->createMissEntry();

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $tags
     *
     * @return self
     **/
    public function prune(array $tags)
    {
        if (!count($tags)) {
            return $this;
        }

        $first = array_shift($tags);
        $query = $this->storage->where('tags', 'like', "%|$first|%");

        foreach ($tags as $tag) {
            $query = $query->orWhere('tags', 'like', "%|$tag|%");
        }

        $deletedIds = [];

        foreach ($query as $cacheId=>$entry) {

            $this->entryCache[$cacheId] = $this->createMissEntry();
            $deletedIds[] = $cacheId;

        }

        $this->writeStorage->clear($deletedIds);

        if ($this->bigStorage) {
            $this->bigStorage->clear($deletedIds);
        }

        return $this;
    }

    /**
     * Just for informational reasons you can retrieve the entryTemplate.
     *
     * @return $this->entryTemplate
     **/
    public function getEntryTemplate()
    {
        return $this->entryTemplate;
    }

    /**
     * Return the max bytes of the main storage.
     *
     * @return int
     **/
    public function getMaxMainStorageBytes()
    {
        return $this->maxMainStorageBytes;
    }

    /**
     * Set the max bytes that will been putted into the main storage.
     * If strlen($value) > $maxMainStorageBytes AND a bigStorage is assigned
     * it will be putted into the bigStorage.
     *
     * @param int $bytes
     *
     * @return self
     **/
    public function setMaxMainStorageBytes($bytes)
    {
        $this->maxMainStorageBytes = $bytes;
        return $this;
    }

    /**
     * Load an entry from memory cache or storage. Return the entry or all found
     * entries if an array ids was passed.
     * This method always returns an entry. If no hit occurs, it returns a
     * cache miss entry. You can check with self::isMiss() if it is a cache miss.
     * This method never returns a exceeded entry. If it did exceed, the entry
     * will be deleted and a miss entry is returned.
     * 
     *
     * @param string|array $id
     *
     * @return array
     **/
    protected function getEntry($id)
    {

        if (!is_array($id)) {
            return $this->getOrCreateMiss($id);
        }

        $hits = [];

        // Look for any previously loaded entries, misses or not
        foreach ($id as $cacheId) {
            if (isset($this->entryCache[$cacheId])) {
                $hits[$cacheId] = $this->entryCache[$cacheId];
            }
        }

        // All found? Return em.
        if (count($hits) == count($id)) {
            return $hits;
        }

        // Filter the remaining ids
        $selectIds = array_filter($id, function ($cacheId) use ($hits) {
            return !isset($hits[$cacheId]);
        });

        // Ask the storage for the remaining ids, put them into cache and $hits
        foreach ($this->storage->where('id', $selectIds) as $cacheId=>$entry) {

            if ($this->isExpired($entry)) {
                $this->forget($cacheId);
                continue;
            }

            $this->entryCache[$cacheId] = $this->castLoadedEntry($entry);
            $hits[$cacheId] = $this->entryCache[$cacheId];
        }

        return $hits;

    }

    protected function getPayload($id, array &$preparedEntry)
    {

        $isPlain = (bool)$preparedEntry['plain'];

        if (!$preparedEntry['outside']) {
            return $isPlain ? $preparedEntry['payload'] : $this->serializer->deserialize($preparedEntry['payload']);
        }


        if (!$this->bigStorage) {
            throw new MisConfiguredException("Cache entry '#$id' with big storage data found, but no big storage assigned.");
        }

        if (isset($this->bigStorage[$id])) {
            return $isPlain ? $this->bigStorage[$id] : $this->serializer->deserialize($this->bigStorage[$id]);
        }

        $bigStorageClass = get_class($this->bigStorage);

        $e = new DataIntegrityException("Main storage has id '#$id', but bigStorage: $bigStorageClass has not.");

        $this->callOnListeners('error', [$e]);

        return null;

    }

    protected function isExpired(array &$preparedEntry)
    {
        return $preparedEntry['valid_until'] != 0 && $preparedEntry['valid_until'] < $this->now();
    }

    protected function isMiss(array &$preparedEntry)
    {
        return $preparedEntry['valid_until'] === -1;
    }

    protected function getOrCreateMiss($id)
    {
        if (isset($this->entryCache[$id])) {
            return $this->entryCache[$id];
        }

        if (!isset($this->storage[$id])) {
            $this->entryCache[$id] = $this->createMissEntry();
            return $this->entryCache[$id];
        }

        $entry = $this->storage[$id];

        if ($this->isExpired($entry)) {
            $this->forget($id);
            $this->entryCache[$id] = $this->createMissEntry();
            return $this->entryCache[$id];
        }

        $this->entryCache[$id] = $this->castLoadedEntry($entry);

        return $this->entryCache[$id];

    }

    protected function createMissEntry()
    {
        $entry = $this->entryTemplate;
        $entry['outside'] = false;
        $entry['plain'] = true;
        $entry['payload'] = null;
        $entry['valid_until'] = -1;
        return $entry;
    }

    /**
     * Writes to big storage if that is possible.
     *
     * @param string $id
     * @param mixed  $value
     *
     * @return bool
     **/
    protected function writeToBigStorageIfNeeded($id, &$value)
    {
        if (!$this->bigStorage) {
            return false;
        }

        if (is_string($value) && strlen($value) <= $this->maxMainStorageBytes) {
            return false;
        }

        try {
            $this->bigStorage[$id] = $value;
            return true;
        } catch (\Exception $e) {
            $msg = "Data of id #$id (type:" . Type::of($value) . ") couldnt be written to big storage";
            $e = new DataIntegrityException($msg, 0, $e);
            $this->callOnListeners('error', [$e]);
        }

        return false;

    }

    /**
     * Cast the values of an loaded entry.
     *
     * @param array  $entry
     *
     * @return array
     **/
    protected function castLoadedEntry(array $entry)
    {
        $entry['valid_until'] = (int)$entry['valid_until'];
        $entry['outside'] = (int)$entry['outside'];
        $entry['plain'] = (int)$entry['plain'];

        if ((bool)$entry['outside']) {
            return $entry;
        }

        if (!(bool)$entry['plain']) {
            $entry['payload'] = $this->serializer->deserialize($entry['payload']);
            $entry['plain'] = 1;
        }

        return $entry;
    }

    /**
     * Return if $value can be serialized.
     *
     * @param mixed $value
     *
     * @return bool
     **/
    protected function canSerialize(&$value)
    {
        return !is_resource($value);
    }

    /**
     * Return if $value should be serialized.
     *
     * @param mixed $value
     *
     * @return bool
     **/
    protected function shouldSerialize(&$value)
    {
        return is_string($value) ? false : $this->canSerialize($value);
    }

    protected function now()
    {
        return time();
    }
}

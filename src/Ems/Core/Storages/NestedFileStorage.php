<?php

namespace Ems\Core\Storages;


use BadMethodCallException;
use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\Filesystem as FilesystemContract;
use Ems\Contracts\Core\HasKeys;
use Ems\Contracts\Core\MimeTypeProvider;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Storage;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Collections\StringList;
use Ems\Core\ConfigurableTrait;
use Ems\Core\Exceptions\KeyLengthException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\LocalFilesystem;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Core\Serializer\JsonSerializer;
use Ems\Core\Url;
use OutOfBoundsException;
use RuntimeException;


/**
 * The NestedFileStorage uses separate SingleFileStorage instances
 * in a directory to store its data.
 * This means, because every SingleFileStorage instance will
 * hold an array of data, you can put only arrays in this
 * storage.
 * It splits the segments of a segmented key (like
 * lang.de.messages.user-saved) into an configurable amount of
 * directories. (lang/de/messages.php key=user-saved)
 * So you can build the typical app configuration stuff
 * or locale stuff into it.
 **/
class NestedFileStorage implements Storage, Configurable, HasKeys
{
    use ConfigurableTrait;
    use SerializeOptions;

    /**
     * @var FilesystemContract
     **/
    protected $filesystem;

    /**
     * @var SerializerContract
     **/
    protected $serializer;

    /**
     * @var MimetypeProvider
     **/
    protected $mimetypes;

    /**
     * @var UrlContract
     **/
    protected $url;

    /**
     * @var array
     **/
    protected $storages = [];

    /**
     * @var array
     **/
    protected $urlToPrefix = [];

    /**
     * @var array
     **/
    protected $changedKeys = [];

    /**
     * @var bool
     **/
    protected $dirExists;

    /**
     * @var string
     **/
    protected $fileExtension = '';

    /**
     * @var callable
     **/
    protected $fileStorageCreator;

    /**
     * @var callable
     **/
    protected $purgeQueue = [];

    /**
     * @var int
     **/
    protected $nestingLevel = 0;

    /**
     * @var int
     **/
    protected $maxNestingLevel = 5;

    /**
     * @var bool
     **/
    protected $allStoragesLoaded = false;

    /**
     * @var array
     **/
    protected $keyUrlCache = [];

    /**
     * @var array
     **/
    protected $subDirQueue = [];

    /**
     * @var array
     **/
    protected $dirCache = [];

    /**
     * @var array
     **/
    protected $defaultOptions = [
        'checksum_method'   => 'crc32',
        'file_locking'      => true
    ];

    /**
     * @param FilesystemContract $filesystem (optional)
     * @param SerializerContract $serializer (optional)
     * @param MimeTypeProvider   $mimetypes  (optional)
     **/
    public function __construct(FilesystemContract $filesystem=null,
                                SerializerContract $serializer=null,
                                MimeTypeProvider $mimetypes=null)
    {
        $this->filesystem = $filesystem ?: new LocalFilesystem;
        $this->serializer = $serializer ?: new JsonSerializer;
        $this->mimetypes = $mimetypes ?: new ManualMimeTypeProvider;

        $this->fileStorageCreator = function ($fileSystem, $serializer, $mimeTypes) {
            return new SingleFileStorage($fileSystem, $serializer);
        };

        if ($this->serializer instanceof JsonSerializer) {
            $this->deserializeOptions[JsonSerializer::AS_ARRAY] = true;
        }
    }

    /**
     * Check if $offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     **/
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {

        list($url, $key) = $this->fileUrlAndKey($offset);

        if ($key) {
            return $this->getOrCreateStorage($url)->offsetExists($key);
        }

        if (isset($this->purgeQueue["$url"])) {
            return false;
        }

        if ($storage = $this->getStorageIfLoaded($url)) {
            return true;
        }

        return $this->filesystem->exists($url);

    }

    /**
     * Get value of $offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     **/
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {

        list($url, $key) = $this->fileUrlAndKey($offset);

        $storage = $this->getOrCreateStorage($url);

        if (!$key) {
            return $storage->toArray();
        }

        return $storage->offsetGet($key);
    }

    /**
     * Set the value of $offset.
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     **/
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->ensureRootDirectory();

        list($url, $key) = $this->fileUrlAndKey($offset);

        $storage = $this->getOrCreateStorage($url);

        if ($key) {
            $storage->offsetSet($key, $value);
            return;
        }

        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new UnsupportedParameterException("Setting values with a path depth of 1 must be array or Traversable");
        }

        $storage->clear();

        foreach ($value as $key=>$value) {
            $storage->offsetSet($key, $value);
        }

    }

    /**
     * Unset $offset.
     *
     * @param mixed $offset
     *
     * @return void
     **/
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        list($url, $key) = $this->fileUrlAndKey($offset);

        $storage = $this->getOrCreateStorage($url);

        if ($key) {
            $storage->offsetUnset($key);
            return;
        }

        $this->purgeQueue["$url"] = $url;

    }

    /**
     * Clears the internal array
     *
     * @param array $keys (optional)
     *
     * @return self
     **/
    public function clear(array $keys=null)
    {
        if ($keys === null) {
            foreach ($this->storages as $storage) {
                $storage->clear();
            }
            return $this;
        }

        if (!$keys) {
            return $this;
        }

        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool (if successfull)
     **/
    public function persist()
    {

        $this->ensureRootDirectory();

        $someoneSaved = false;
        foreach ($this->storages as $url=>$storage) {
            $this->ensureStorageDirectory($url);
            if ($storage->persist()) {
                $someoneSaved = true;
            }
        }

        foreach ($this->purgeQueue as $url) {
            $storage = $this->getOrCreateStorage($url);
            if ($storage->purge()) {
                $someoneSaved = true;
            }
            $this->removeStorageIfLoaded($url);
        }

        $this->purgeQueue = [];

        return $someoneSaved;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $keys (optional)
     *
     * @return bool (if successfull)
     **/
    public function purge(array $keys=null)
    {

        if ($keys === null) {

            $someonePurged = false;

            $storages = $this->preloadAllStorages();

            foreach ($storages as $url=>$storage) {

                if ($result = $storage->purge()) {
                    $someonePurged = true;
                }

                $this->removeStorageIfLoaded($url);

            }

            return $someonePurged;
        }

        if (!$keys) {
            return false;
        }

        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this->persist();

    }

    /**
     * @inheritDoc
     */
    public function isBuffered()
    {
        return true;
    }


    /**
     * The directory url
     *
     * @return UrlContract
     **/
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the directory url
     *
     * @param UrlContract $url
     *
     * @return self
     **/
    public function setUrl(UrlContract $url)
    {
        $this->url = $url;
        $this->dirExists = null;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function storageType()
    {
        return self::FILESYSTEM;
    }

    /**
     * {@inheritdoc}
     *
     * @return StringList
     **/
    public function keys()
    {
        $keys = [];

        $storages = $this->preloadAllStorages();

        foreach ($storages as $url=>$storage) {

            $prefix = $this->keyPrefix($url);

            $keys[$prefix] = true;

            foreach ($storage->keys() as $key) {
                $keys["$prefix.$key"] = true;
            }
        }

        return new StringList(array_keys($keys));
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function toArray()
    {
        $data = [];

        $storages = $this->preloadAllStorages();

        foreach ($storages as $url=>$storage) {

            $prefix = $this->keyPrefix($url);

            $data[$prefix] = $storage->toArray();

        }

        return $data;
    }

    /**
     * @see self::setNestingLevel()
     *
     * @return int
     **/
    public function getNestingLevel()
    {
        return $this->nestingLevel;
    }

    /**
     * Set the director nesting level.A nesting level of 1 means that
     * there will be one directory for the first path segment of the
     * keys. Like en.formats.numbers will be /en/formats.json.
     * A nesting level of 2 would be en.formats.numbers:
     * en/formats/numbers.txt.
     * So the nesting level is the extact amount of directories created
     * for every path.
     *
     * @param int $level
     *
     * @return self
     **/
    public function setNestingLevel($level)
    {
        if ($level == $this->nestingLevel) {
            return $this;
        }

        if (count($this->storages)) {
            throw new BadMethodCallException("You can change the nesting level only before the first storage was loaded.");
        }

        if ($level > $this->maxNestingLevel) {
            throw new OutOfBoundsException("This storage only accepts a max nesting level of $this->maxNestingLevel. You passed $level.");
        }

        $this->nestingLevel = $level;

        return $this;
    }

    /**
     * Assign a callable to create the file storage
     *
     * @param callable $creator
     *
     * @return self
     **/
    public function createFileStorageBy(callable $creator)
    {
        $this->fileStorageCreator = $creator;
        return $this;
    }

    /**
     * Return an already loaded storage or create one
     *
     * @param UrlContract $url
     *
     * @return SingleFileStorage
     **/
    protected function getOrCreateStorage(UrlContract $url)
    {
        $arrayKey = "$url";

        if (isset($this->storages[$arrayKey])) {
            return $this->storages[$arrayKey];
        }

        $this->storages[$arrayKey] = $this->createFileStorage();
        $this->copyOptionsToFileStorage($this->storages[$arrayKey]);
        $this->storages[$arrayKey]->setUrl($url);

        if (!$this->nestingLevel) {
            return $this->storages[$arrayKey];
        }

        $subDirs = [];

        for ($i=0; $i<$this->nestingLevel; $i++) {
            $subDirs[] = (string)$url->pop();
        }

        sort($subDirs); // Should sort by length

        $this->subDirQueue[$arrayKey] = $subDirs;

        return $this->storages[$arrayKey];
    }

    /**
     * Return an already loaded storage or create one
     *
     * @param UrlContract|string $url
     *
     * @return SingleFileStorage|null
     **/
    protected function getStorageIfLoaded($url)
    {
        $arrayKey = "$url";

        if ($this->hasStorage($arrayKey)) {
            return $this->storages[$arrayKey];
        }

        return null;
    }

    /**
     * Return an already loaded storage or create one
     *
     * @param UrlContract|string $url
     *
     * @return SingleFileStorage|null
     **/
    protected function removeStorageIfLoaded($url)
    {
        $arrayKey = "$url";

        if (isset($this->storages[$arrayKey])) {
            unset($this->storages[$arrayKey]);
        }

        return null;
    }

    /**
     * Return an already loaded storage or create one
     *
     * @param UrlContract|string $url
     *
     * @return SingleFileStorage|null
     **/
    protected function hasStorage($url)
    {
        return isset($this->storages["$url"]);
    }

    /**
     * Read all storages to have all the data.
     *
     * @return array
     */
    protected function preloadAllStorages()
    {

        if ($this->allStoragesLoaded) {
            return $this->storages;
        }

        $filesAndDirs = $this->filesystem->listDirectory($this->url, true, false);

        foreach ($filesAndDirs as $file) {

            if ($this->filesystem->extension($file) != $this->fileExtension()) {
                continue;
            }

            $this->getOrCreateStorage(new Url($file));

        }

        $this->allStoragesLoaded = true;

        return $this->storages;

    }

    /**
     * Ensure the base directory exists
     **/
    protected function ensureRootDirectory()
    {
        if ($this->dirExists === true) {
            return;
        }

        $this->ensureDirectory((string)$this->filePath('foo')->pop()); // trigger errors if unconfigured

        $this->dirExists = true;
    }

    /**
     * @param string $url
     */
    protected function ensureStorageDirectory($url)
    {
        if (!isset($this->subDirQueue[$url])) {
            return;
        }
        foreach ($this->subDirQueue[$url] as $dir) {
            $this->ensureDirectory($dir);
        }
    }

    /**
     * Ensure the base directory exists
     *
     * @param string $dir
     **/
    protected function ensureDirectory($dir)
    {
        if (isset($this->dirCache[$dir])) {
            return;
        }

        if ($this->filesystem->isDirectory($dir)) {
            $this->dirCache[$dir] = true;
            return;
        }

        if (!@$this->filesystem->makeDirectory($dir)) {
            throw new RuntimeException("Cannot create base directory '$dir'");
        }

        $this->dirCache[$dir] = true;
    }

    /**
     * Return the filepath for a key ($offset)
     *
     * @param string $offset
     *
     * @return UrlContract
     **/
    protected function filePath($offset)
    {
        return $this->urlOrFail()->append($this->fileName($offset));
    }

    /**
     * Calculate the key prefix of a storage url. If you have for example a
     * nesting level of 1 and the path of the filestorage is "de/messages.json" the key
     * prefix is "de.messages".
     *
     * @param $storageUrl
     *
     * @return string
     */
    protected function keyPrefix($storageUrl)
    {
        $urlString = "$storageUrl";

        if (isset($this->urlToPrefix[$urlString])) {
            return $this->urlToPrefix[$urlString];
        }

        $withoutPath = str_replace((string)$this->urlOrFail(), '', $urlString);
        $withoutPath = ltrim($withoutPath, '/');

        $extensionLength = strlen($this->fileExtension())+1;
        $withoutExtension = substr($withoutPath, 0, strlen($withoutPath) - ($extensionLength));
        $prefix = str_replace('/','.', $withoutExtension);

        $this->urlToPrefix[$urlString] = $prefix;

        return $prefix;
    }

    /**
     * Return the fileName for a key ($offset)
     *
     * @param string $offset
     *
     * @return string
     **/
    protected function fileName($offset)
    {
        if (preg_match('/^[\pL\pM\pN_.-]+$/u', $offset) > 0) {
            return $offset . '.' . $this->fileExtension();
        }
        throw new UnsupportedParameterException("The key has to be filesystem compatible");
    }

    /**
     * Create a file storage object
     *
     * @return Storage
     **/
    protected function createFileStorage()
    {
        return call_user_func(
            $this->fileStorageCreator,
            $this->filesystem,
            $this->serializer,
            $this->mimetypes
        );
    }

    /**
     * Apply own options to FileStorage
     *
     * @param SingleFileStorage $fileStorage
     */
    protected function copyOptionsToFileStorage(SingleFileStorage $fileStorage)
    {
        foreach ($this->supportedOptions() as $key) {
            $fileStorage->setOption($key, $this->getOption($key));
        }
        $fileStorage->setSerializeOptions($this->serializeOptions);
        $fileStorage->setDeserializeOptions($this->deserializeOptions);
    }

    /**
     * Get the file extension for the cache files
     *
     * @return string
     **/
    protected function fileExtension()
    {
        if (!$this->fileExtension) {
            $mimeType = $this->serializer->mimeType();
            $this->fileExtension = $this->mimetypes->fileExtensions($mimeType)[0];
        };
        return $this->fileExtension;
    }

    /**
     * Extract the file url and the key to query the storage.
     *
     * @param $key
     *
     * @return array(UrlContract, string)
     */
    protected function fileUrlAndKey($key)
    {

        if (isset($this->keyUrlCache[$key])) {
            return $this->keyUrlCache[$key];
        }

        $parts = explode('.', $key);
        $url = $this->urlOrFail();
        $partCount = count($parts);

        if ($partCount <= $this->nestingLevel) {
            $minLength = $this->nestingLevel+1;
            $msg = "The key segments has to have a minimum count of $minLength (nesting level: $this->nestingLevel)";
            throw (new KeyLengthException($msg))->setMinSegments($minLength);
        }

        $subKey = [];

        for ($i=0; $i<$partCount; $i++) {


            if ($i < $this->nestingLevel) {
                $url = $url->append($parts[$i]);
                continue;
            }


            if ($i == $this->nestingLevel) {
                $url = $url->append($this->fileName($parts[$i]));
                continue;
            }

            $subKey[] = $parts[$i];
        }

        $this->keyUrlCache[$key] = [$url, implode('.', $subKey)];

        return $this->keyUrlCache[$key];

    }

    /**
     * Returns the assigned url or fails
     *
     * @return Url
     *
     * @throws UnConfiguredException
     **/
    protected function urlOrFail()
    {
        if (!$this->url) {
            throw new UnConfiguredException("Assign a directory via setUrl()");
        }
        return $this->url;
    }

}

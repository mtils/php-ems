<?php

namespace Ems\Core\Storages;


use Ems\Contracts\Core\UnbufferedStorage;
use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Filesystem as FilesystemContract;
use Ems\Contracts\Core\MimetypeProvider;
use Ems\Core\Collections\StringList;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\ConfigurableTrait;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\LocalFilesystem;
use Ems\Core\ManualMimetypeProvider;
use Ems\Core\Serializer as NativeSerializer;
use Ems\Core\Exceptions\DataIntegrityException;
use RuntimeException;

/**
 * The FileStorage is a completely unbuffered storage.
 * Almost every call to method on it will result in filesystem access.
 * So just it internally or with a proxy to implement buffering around it.
 **/
class FileStorage implements UnbufferedStorage, Configurable
{
    use ConfigurableTrait;
    use SerializeOptions;

    /**
     * @var SerializerContract
     **/
    protected $serializer;

    /**
     * @var FilesystemContract
     **/
    protected $filesystem;

    /**
     * @var MimetypeProvider
     **/
    protected $mimetypes;

    /**
     * @var string
     **/
    protected $fileExtension = '';

    /**
     * @var callable
     **/
    protected $checksummer;

    /**
     * @var UrlContract
     **/
    protected $url;

    /**
     * @var array
     **/
    protected $defaultOptions = [
        'checksum_method'   => 'crc32',
        'file_locking'      => true
    ];

    /**
     * @param FilesystemContract $filesystem
     * @param SerializerContract $serializer
     **/
    public function __construct(SerializerContract $serializer=null,
                                FilesystemContract $filesystem=null,
                                MimetypeProvider $mimetypes=null)
    {
        $this->serializer = $serializer ?: new NativeSerializer;
        $this->filesystem = $filesystem ?: new LocalFilesystem;
        $this->mimetypes = $mimetypes ?: new ManualMimetypeProvider;
        $this->checksummer = function ($method, $data) {
            return $method == 'strlen' ? strlen($data) : hash($method, $data);
        };
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
        return $this;
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
        return $this->filesystem->exists($this->fileOfKey($offset));
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
        $file = $this->fileOfKey($offset);
        return $this->serializer->deserialize($this->filesystem->contents($file));
    }

    /**
     * Put data into this storage. At least write a file.
     *
     * @param string $offset
     * @param mixed  $value
     **/
    public function offsetSet($offset, $value)
    {
        $this->ensureDirectory($this->urlOrFail());

        $this->checkKey($offset);

        $fileUrl = $this->fileOfKey($offset);
        $hashMethod = $this->getOption('checksum_method');

        if (!$hashMethod) {
            $this->filesystem->write(
                $fileUrl,
                $this->serializer->serialize($value, $this->serializeOptions),
                $this->getOption('file_locking')
            );

            return;
        }

        $blob = $this->serializer->serialize($value, $this->serializeOptions);

        $this->filesystem->write(
                $fileUrl,
                $blob,
                $this->getOption('file_locking')
            );

        $checksum = $this->createChecksum($hashMethod, $blob);

        $savedBlob = $this->filesystem->contents($fileUrl, 0, false);

        $this->checkData($hashMethod, $savedBlob, $checksum);
    }

    /**
     * Unset $offset. If the file or the directory does not exist, just ignore
     * the error
     *
     * @param string $offset
     **/
    public function offsetUnset($offset)
    {
        $url = $this->urlOrFail();

        // Just ignore unsetting keys that do not exist
        if (!$this->filesystem->isDirectory($this->urlOrFail())) {
            return;
        }

        $fileName = $this->fileOfKey($offset);

        if ($this->filesystem->exists($fileName)) {
            $this->filesystem->delete($fileName);
        }
    }

    /**
     * @param array $keys (optional)
     *
     * @return self
     **/
    public function clear(array $keys=null)
    {
        $keys = $keys === null ? $this->keys() : $keys;

        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return StringList
     **/
    public function keys()
    {

        $keys = new StringList();
        $url = $this->urlOrFail();

        if (!$this->filesystem->isDirectory($this->urlOrFail())) {
            return $keys;
        }

        $fileExtension = $this->fileExtension();

        foreach ($this->filesystem->listDirectory($this->urlOrFail()) as $fileName) {

            if ($this->filesystem->extension($fileName) != $fileExtension) {
                continue;
            }

            if ($this->filesystem->isDirectory($fileName)) {
                continue;
            }

            $keys->append($this->keyOfFile($fileName));

        }

        return $keys;
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
        $data = [];

        foreach ($this->keys() as $key) {
            $data[$key] = $this->offsetGet($key);
        }

        return $data;
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
     * Assign a custom callable to create the checksum. The checksum dont has
     * to be a paranoid secure hash. It is just to ensure data integrity and
     * should make cache attacks (a little) more difficult
     *
     * @param callable $checksummer
     *
     * @return self
     **/
    public function createChecksumBy(callable $checksummer)
    {
        $this->checksummer = $checksummer;
        return $this;
    }

    /**
     * Check the data integrity by the passed checkum. If invalid throw an
     * exception
     *
     * @param string $method
     * @param string $data
     * @param string $checksum
     *
     * @throws Ems\Contracts\Core\Errors\DataCorruption
     **/
    protected function checkData($method, &$data, $checksum)
    {
        $freshChecksum = $this->createChecksum($method, $data);
        if ($freshChecksum != $checksum) {
            throw new DataIntegrityException('Checksum of file '.$this->getUrl()." failed. ($freshChecksum != $checksum)");
        }
    }

    /**
     * Checks for filesystem compatible keys.
     *
     * @param string $offset
     *
     * @throws UnsupportedParameterException
     **/
    protected function checkKey($offset)
    {
        if (preg_match('/^[\pL\pM\pN_-]+$/u', $offset) == 0) {
            throw new UnsupportedParameterException("The key has to be filesystem compatible");
        }
    }

    /**
     * Create the data checksum
     *
     * @param string $method
     * @param string $data
     *
     * @return string
     **/
    protected function createChecksum($method, &$data)
    {
        return call_user_func($this->checksummer, $method, $data);
    }

    protected function keyOfFile($fileName)
    {
        return $this->filesystem->name($fileName);
    }

    protected function fileOfKey($key)
    {
        return $this->urlOrFail()->append("$key." . $this->fileExtension());
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

    /**
     * Ensure the base directory exists
     **/
    protected function ensureDirectory($dir)
    {
        if ($this->filesystem->isDirectory($dir)) {
            return true;
        }

        if (!@$this->filesystem->makeDirectory($dir)) {
            throw new RuntimeException("Cannot create base directory '$dir'");
        }

        return true;
    }

}

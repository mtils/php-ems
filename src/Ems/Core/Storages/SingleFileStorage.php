<?php

namespace Ems\Core\Storages;


use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\Filesystem as FilesystemContract;
use Ems\Contracts\Core\HasKeys;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Storage;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\ConfigurableTrait;
use Ems\Core\Exceptions\DataIntegrityException;
use Ems\Core\Support\BootingArrayData;

/**
 * Class SingleFileStorage
 *
 * In opposite to FileStorage a SingleFileStorage serializes all data into one
 * single file.
 *
 * @package Ems\Core\Storages
 */
class SingleFileStorage implements Storage, Configurable, HasKeys
{
    use BootingArrayData;
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
    public function __construct(FilesystemContract $filesystem, SerializerContract $serializer)
    {
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
        $this->checksummer = function ($method, $data) {
            return $method == 'strlen' ? strlen($data) : hash($method, $data);
        };
    }

    /**
     * The file url
     *
     * @return UrlContract
     **/
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the file url
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
     * {@inheritdoc}
     *
     * @return bool (if successful)
     **/
    public function persist()
    {

        $blob = $this->serializer->serialize($this->_attributes, $this->serializeOptions);

        $result = (bool)$this->filesystem->write($this->url, $blob, $this->getOption('file_locking'));

        if (!$hashMethod = $this->getOption('checksum_method')) {
            return $result;
        }

        $checksum = $this->createChecksum($hashMethod, $blob);

        $savedBlob = $this->filesystem->read($this->url);

        $this->checkData($hashMethod, $savedBlob, $checksum);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $keys (optional)
     *
     * @return bool (if successful)
     **/
    public function purge(array $keys=null)
    {
        if ($keys === null) {
            $this->_attributes = [];
            return $this->filesystem->delete($this->url);
        }

        if (!$keys) {
            return false;
        }

        $this->bootOnce();

        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this->persist();
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
     * @inheritDoc
     */
    public function isBuffered()
    {
        return true;
    }


    /**
     * Assign a custom callable to create the checksum. The checksum don't has
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
     * Load the data from filesystem
     **/
    protected function autoAssignAttributes()
    {
        if (!$this->filesystem->exists($this->url)) {
            return;
        }

        $blob = $this->filesystem->read($this->url);

        $this->fillAttributes($this->serializer->deserialize($blob, $this->deserializeOptions));

    }

    /**
     * Check the data integrity by the passed checksum. If invalid throw an
     * exception
     *
     * @param string $method
     * @param string $data
     * @param string $checksum
     *
     * @throws \Ems\Contracts\Core\Errors\DataCorruption
     **/
    protected function checkData($method, $data, $checksum)
    {
        $freshChecksum = $this->createChecksum($method, $data);
        if ($freshChecksum != $checksum) {
            throw new DataIntegrityException('Checksum of file '.$this->getUrl()." failed. ($freshChecksum != $checksum)");
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
    protected function createChecksum($method, $data)
    {
        return call_user_func($this->checksummer, $method, $data);
    }

}

<?php

namespace Ems\Core\Storages;


use Ems\Contracts\Core\Storage;
use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Filesystem as FilesystemContract;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\ConfigurableTrait;
use Ems\Core\Support\ArrayAccessMethods;
use Ems\Core\Exceptions\DataIntegrityException;

class FileStorage implements Storage, Configurable
{
    use ArrayAccessMethods;
    use ConfigurableTrait;

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
     * @return bool (if successfull)
     **/
    public function persist()
    {
        $blob = $this->serializer->serialize($this->_attributes);

        $result = (bool)$this->filesystem->write($this->url, $blob, $this->getOption('file_locking'));

        if (!$hashMethod = $this->getOption('checksum_method')) {
            return $result;
        }

        $checksum = $this->createChecksum($hashMethod, $blob);

        $savedBlob = $this->filesystem->contents($this->url, 0, false);

        $this->checkData($hashMethod, $savedBlob, $checksum);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool (if successfull)
     **/
    public function purge()
    {
        $this->_attributes = [];
        return $this->filesystem->delete($this->url);
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
     * Load the data from filesystem
     **/
    protected function fillAttributes()
    {
        if (!$this->filesystem->exists($this->url)) {
            return;
        }

        $blob = $this->filesystem->contents($this->url);
        $this->_attributes = $this->serializer->deserialize($blob);
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

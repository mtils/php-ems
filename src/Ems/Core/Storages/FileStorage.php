<?php

namespace Ems\Core\Storages;


use Ems\Contracts\Core\Storage;
use Ems\Core\Support\ArrayAccessMethods;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Filesystem as FilesystemContract;
use Ems\Contracts\Core\Url as UrlContract;

class FileStorage implements Storage
{
    use ArrayAccessMethods;

    /**
     * @var FilesystemContract
     **/
    protected $filesystem;

    /**
     * @var SerializerContract
     **/
    protected $serializer;

    /**
     * @var UrlContract
     **/
    protected $url;

    /**
     * @param FilesystemContract $filesystem
     * @param SerializerContract $serializer
     **/
    public function __construct(FilesystemContract $filesystem, SerializerContract $serializer)
    {
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
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
        return (bool)$this->filesystem->write($this->url, $this->serializer->serialize($this->_attributes));
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
     * Load the data from filesystem
     **/
    protected function fillAttributes()
    {
        if (!$this->filesystem->exists($this->url)) {
            return;
        }
        $this->_attributes = $this->serializer->deserialize($this->filesystem->contents($this->url));
    }
}

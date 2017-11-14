<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Content;
use Ems\Contracts\Core\Filesystem;
use Ems\Core\Support\StringableTrait;
use Iterator;
use LogicException;


class BinaryContent implements Content
{
    use StringableTrait;

    /**
     * @var Filesystem
     **/
    protected $filesystem;

    /**
     * @var Url
     **/
    protected $url;

    /**
     * @var string
     **/
    protected $mimeType;

    /**
     * @var callable
     **/
    protected $iteratorCreator;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set the mimeType (once)
     *
     * @param string $mimeType
     *
     * @return self
     **/
    public function setMimeType($mimeType)
    {
        if ($this->mimeType && $mimeType != $this->mimeType) {
            throw new LogicException('You can only configure a content once');
        }
        $this->mimeType = $mimeType;
        return $this;

    }

    /**
     * {@inheritdoc}
     *
     * @return Url
     **/
    public function url()
    {
        return $this->url;
    }

    /**
     * Set the url.
     *
     * @param string $url
     *
     * @return self
     **/
    public function setUrl($url)
    {
        $url = "$url"; // Cast Url objects to string

        if ($this->url && $url != $this->url) {
            throw new LogicException('You can only configure a content once');
        }
        $this->url = $url;
        return $this;
    }

    /**
     * Return the size in bytes
     *
     * @return int
     **/
    public function count()
    {
        return $this->getIterator()->count($this->url);
    }

    /**
     * Return an iterator to iterate bytes by bytes
     *
     * @return Iterator
     **/
    public function getIterator()
    {
        if ($this->iteratorCreator) {
            return call_user_func($this->iteratorCreator, $this, $this->filesystem);
        }

        return $this->createIterator();
    }

    /**
     * Set a custom callable to create the iterator
     *
     * @param callable $creator
     *
     * @return self
     **/
    public function createIteratorBy(callable $creator)
    {
        $this->iteratorCreator = $creator;
        return $this;
    }

    /**
     * Renders the result. Is just inside its own method to allow easy
     * overwriting __toString().
     *
     * @return string
     **/
    public function toString()
    {
        return $this->filesystem->read($this->url);
    }

    /**
     * Create the iterator
     *
     * @return Iterator
     **/
    protected function createIterator()
    {
        return new BinaryReadIterator($this->url, $this->filesystem);
    }
}

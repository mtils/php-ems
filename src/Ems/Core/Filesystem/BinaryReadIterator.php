<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem;
use Ems\Core\LocalFilesystem;
use Iterator;

/**
 * The BinaryReadIterator is a iterator which allows to read
 * binary files in chunks.
 *
 * @sample foreach (new ReadIterator($file) as $chunk) ...
 **/
class BinaryReadIterator implements Iterator
{
    /**
     * @var string
     **/
    protected $filePath = '';

    /**
     * @var Filesystem
     **/
    protected $filesystem;

    /**
     * @var string
     **/
    protected $currentBytes;

    /**
     * @var int
     **/
    protected $chunkSize = 4096;

    /**
     * @var int
     **/
    private $position = 0;

    /**
     * @var resource
     **/
    protected $handle;

    /**
     * @param string     $filePath   (optional)
     * @param Filesystem $filesystem (optional)
     **/
    public function __construct($filePath = '', Filesystem $filesystem = null)
    {
        $this->position = 0;
        $this->filePath = $filePath;
        $this->setFilesystem($filesystem ?: new LocalFilesystem());
    }

    /**
     * Return the bytes which will be readed in one iteration.
     *
     * @return int
     **/
    public function getChunkSize()
    {
        return $this->chunkSize;
    }

    /**
     * @see self::getChunkSize()
     *
     * @param int $chunkSize
     *
     * @return self
     **/
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    /**
     * @return string
     **/
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     *
     * @return self
     **/
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
        $this->releaseHandle();

        return $this;
    }

    /**
     * @return Filesystem
     **/
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @param Filesystem $filesystem
     *
     * @return self
     **/
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Reset the internal pointer to the beginning.
     **/
    public function rewind()
    {
        $this->currentBytes = $this->readNext(
            $this->initHandle($this->getFilePath()),
            $this->chunkSize
        );
        $this->position = $this->currentBytes === null ? -1 : 0;
    }

    /**
     * @return string
     **/
    public function current()
    {
        return $this->currentBytes;
    }

    /**
     * @return int
     **/
    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->currentBytes = $this->readNext($this->handle, $this->chunkSize);
        $this->position = $this->currentBytes === null ? -1 : $this->position + 1;
    }

    /**
     * @return bool
     **/
    public function valid()
    {
        return is_resource($this->handle) && $this->position !== -1;
    }

    /**
     * Release file handle on destruction.
     **/
    public function __destruct()
    {
        $this->releaseHandle();
    }

    /**
     * Close the handle if opened.
     **/
    protected function releaseHandle()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
        $this->handle = null;
    }

    /**
     * @return bool
     **/
    protected function hasHandle()
    {
        return is_resource($this->handle);
    }

    /**
     * Read the next chunk and return it.
     *
     * @param resource $handle
     * @param int      $chunkSize
     *
     * @return string|null
     **/
    protected function readNext($handle, $chunkSize)
    {
        if (feof($handle)) {
            return;
        }

        return $this->filesystem->read($this->filePath, $chunkSize, $handle);
    }

    /**
     * Create or rewind the handle.
     *
     * @param string $filePath
     *
     * @return resource
     **/
    protected function initHandle($filePath)
    {
        if ($this->hasHandle()) {
            rewind($this->handle);

            return $this->handle;
        }

        $this->handle = $this->filesystem->handle($filePath);

        return $this->handle;
    }
}

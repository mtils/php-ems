<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\ContentIterator;
use Ems\Core\LocalFilesystem;
use Iterator;

/**
 * The LineReadIterator is a iterator which allows to read
 * ascii files line by line.
 *
 * @sample foreach (new LineReadIterator($file) as $line) ...
 **/
trait ReadIteratorTrait
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
    protected $currentValue;

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
        $this->onFileChanged();

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
        $this->onRewind();
        $this->currentValue = $this->readNext(
            $this->initHandle($this->getFilePath()),
            $this->chunkSize
        );
        $this->position = $this->currentValue === null ? -1 : 0;
    }

    /**
     * @return string
     **/
    public function current()
    {
        return $this->currentValue;
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
        $this->currentValue = $this->readNext($this->handle, $this->chunkSize);
        $this->position = $this->currentValue === null ? -1 : $this->position + 1;
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
    public function releaseHandle()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
        $this->handle = null;
    }

    /**
     * Hook into a file change.
     **/
    protected function onFileChanged()
    {
    }

    /**
     * Hook into the rewind call.
     **/
    protected function onRewind()
    {
    }

    /**
     * @return bool
     **/
    protected function hasHandle()
    {
        return is_resource($this->handle);
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

        $this->handle = $this->createHandle($filePath);

        return $this->handle;
    }

    /**
     * @param string $filePath
     *
     * @return resource
     **/
    protected function createHandle($filePath)
    {
        return $this->filesystem->handle($filePath);
    }

    /**
     * Clean the line (remove line breaks).
     *
     * @param string $line
     *
     * @return string
     **/
    protected function readLine($handle, $chunkSize)
    {
        $line = $chunkSize ? fgets($handle, $chunkSize) : fgets($handle);

        return rtrim($line, "\n\r");
    }
}

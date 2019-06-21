<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\None;
use Ems\Contracts\Core\Stream;

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
     * @var Stream
     */
    protected $stream;

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
    private $position = 0;

    /**
     * @var resource
     **/
    protected $handle;


    /**
     * @return string
     **/
    public function getFilePath()
    {
        if ($this->filePath) {
            return $this->filePath;
        }
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
        $this->stream->rewind();
        $this->position = $this->stream->valid() ? 0 : -1;
        $this->currentValue = new None();
    }

    /**
     * @return string
     **/
    public function current()
    {
        if ($this->position === 0 && $this->currentValue instanceof None) {
            $this->currentValue = $this->readNext($this->stream->resource(), $this->stream->getChunkSize());
        }
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
        $this->currentValue = $this->readNext(
            $this->stream->resource(),
            $this->stream->getChunkSize()
        );
        $this->position = $this->currentValue === null ? -1 : $this->position + 1;
    }

    /**
     * @return bool
     **/
    public function valid()
    {
        return $this->stream->valid() && $this->position !== -1;
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
        if ($this->stream) {
            $this->stream->close();
        }
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
     * Clean the line (remove line breaks).
     *
     * @param resource $handle
     * @param int      $chunkSize
     *
     * @return string
     **/
    protected function readLine($handle, $chunkSize)
    {
        $line = $chunkSize ? fgets($handle, $chunkSize) : fgets($handle);

        return rtrim($line, "\n\r");
    }
}

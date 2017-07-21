<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\ContentIterator;
use Ems\Core\LocalFilesystem;
use Iterator;

/**
 * The BinaryReadIterator is a iterator which allows to read
 * binary files in chunks.
 *
 * @sample foreach (new ReadIterator($file) as $chunk) ...
 **/
class BinaryReadIterator implements ContentIterator
{
    use ReadIteratorTrait;

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
     * Return the amount of bytes
     *
     * @return int
     **/
    public function count()
    {
        return $this->filesystem->size($this->getFilePath());
    }
}

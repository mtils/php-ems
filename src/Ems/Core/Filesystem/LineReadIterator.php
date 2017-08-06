<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\ContentIterator;
use Ems\Core\LocalFilesystem;
use Iterator;

/**
 * The LineReadIterator is an iterator which allows to read
 * ascii files line by line.
 *
 * @sample foreach (new LineReadIterator($file) as $line) ...
 **/
class LineReadIterator implements ContentIterator
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
        $this->chunkSize = 0;
        $this->setFilesystem($filesystem ?: new LocalFilesystem());
    }

    /**
     * Read the next line and return it. Skip empty lines. The line breaks
     * will be removed.
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

        $line = $this->readLine($handle, $chunkSize);

        return $line === '' ? $this->readNext($handle, $chunkSize) : $line;
    }

    /**
     * Return the amount of lines.
     *
     * @return int
     **/
    public function count()
    {
        $handle = $this->createHandle($this->getFilePath());
        $lineCount = 0;

        while (!feof($handle)) {
            $line = $this->readLine($handle, null);
            if ($line !== '') {
                ++$lineCount;
            }
        }

        fclose($handle);

        return $lineCount;
    }

    /**
     * @param string $filePath
     *
     * @return resource
     **/
    protected function createHandle($filePath)
    {
        return $this->filesystem->handle($filePath, 'r');
    }

}

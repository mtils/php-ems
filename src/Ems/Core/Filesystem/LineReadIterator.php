<?php

namespace Ems\Core\Filesystem;

use Countable;
use Ems\Contracts\Core\Stream;
use Iterator;

/**
 * The LineReadIterator is an iterator which allows to read
 * ascii files line by line.
 *
 * @sample foreach (new LineReadIterator($file) as $line) ...
 **/
class LineReadIterator implements Iterator, Countable
{
    use ReadIteratorTrait;

    /**
     * @param string     $filePath   (optional)
     * @param Stream $stream (optional)
     **/
    public function __construct($filePath = '', Stream $stream = null)
    {
        $this->position = 0;
        $this->filePath = $filePath;
        $this->stream = $stream ?: new FileStream($filePath);
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
            return null;
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
        if (!$this->stream) {
            return 0;
        }
        $this->stream->rewind();
        $handle = $this->stream->resource();
        $lineCount = 0;

        while (!feof($handle)) {
            $line = $this->readLine($handle, null);
            if ($line !== '') {
                ++$lineCount;
            }
        }

        return $lineCount;
    }

}

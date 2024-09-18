<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\None;
use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Type;

/**
 * The LineReadIterator is a iterator which allows to read
 * ascii files line by line.
 *
 * @sample foreach (new LineReadIterator($file) as $line) ...
 **/
trait ReadIteratorTrait
{

    /**
     * @var Stream
     */
    protected $stream;

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
     * Reset the internal pointer to the beginning.
     **/
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
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

    /**
     * @param string|object|Stream $filePathOrStream
     *
     * @return FileStream
     */
    protected function makeStream($filePathOrStream)
    {
        if ($filePathOrStream instanceof Stream) {
            return $filePathOrStream;
        }
        if (Type::isStringLike($filePathOrStream)) {
            return new FileStream($filePathOrStream);
        }
        throw new TypeException('filePathOrStream has to be string like or a stream');
    }
}

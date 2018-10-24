<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 19:15
 */

namespace Ems\Core;

use Ems\Contracts\Core\Connection;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Filesystem\FileStream;

class FilesystemConnection implements Connection
{
    /**
     * @var UrlContract
     */
    protected $url;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var FileStream
     */
    protected $stream;

    /**
     * @var bool
     */
    protected $didWrite = false;

    /**
     * FilesystemConnection constructor.
     *
     * @param UrlContract|string $url
     */
    public function __construct($url)
    {
        $this->url = $url instanceof UrlContract ? $url : new Url($url);
    }

    /**
     * Read some bytes from the connection. This is handled by read
     *
     * @param int $bytes
     * @return string
     */
    public function read($bytes=0)
    {
        // If the connection wrote into the resource the handle will be placed
        // at the end and nothing will be returned
        if ($this->didWrite) {
            $this->stream = null;
        }

        if (!$bytes) {
            return $this->stream()->toString();
        }

        $stream = $this->stream();
        $stream->setChunkSize($bytes);

        if (!$stream->isOpen()) {
            $stream->rewind();
        } else {
            $stream->next();
        }

        return $stream->current();
    }

    /**
     * Write some bytes into the connection.
     *
     * @param string $content
     *
     * @return int The written bytes
     */
    public function write($content)
    {

        $bytes =  $this->stream()->write($content);
        $this->didWrite = true;
        return $bytes;
    }

    /**
     * @return self
     */
    public function open()
    {
        $this->stream()->open();
        return $this;
    }

    /**
     * @return self
     */
    public function close()
    {
        if (!$this->stream) {
            return $this;
        }

        if ($this->stream->isOpen()) {
            $this->stream->close();
        }
        $this->stream = null;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        if (!$this->stream) {
            return false;
        }
        return (bool)$this->stream()->isOpen();
    }

    /**
     * @return resource
     */
    public function resource()
    {
        return $this->stream()->resource();
    }

    /**
     * @return UrlContract
     */
    public function url()
    {
        return $this->url;
    }

    /**
     * @return FileStream
     */
    protected function stream()
    {
        if (!$this->stream) {
            $this->stream = $this->createStream();
        }
        return $this->stream;
    }

    /**
     * @return FileStream
     */
    protected function createStream()
    {
        return new FileStream($this->url());
    }
}
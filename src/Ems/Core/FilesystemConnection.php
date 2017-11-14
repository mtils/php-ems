<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 19:15
 */

namespace Ems\Core;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Core\Connection;
use RuntimeException;

class FilesystemConnection implements Connection
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var UrlContract
     */
    protected $url;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * FilesystemConnection constructor.
     *
     * @param UrlContract        $url
     * @param Filesystem $filesystem (optional)
     */
    public function __construct(UrlContract $url, Filesystem $filesystem=null)
    {
        $this->url = $url;
        $this->filesystem = $filesystem ?: new LocalFilesystem();
    }

    /**
     * Read some bytes from the connection. This is handled by read
     *
     * @param int $bytes
     * @return string
     */
    public function read($bytes=0)
    {
        return $this->filesystem->read((string)$this->url, $bytes, $this->resource());
    }

    /**
     * Write some bytes into the connection.
     *
     * @param string $content
     * @param bool   $lock (default:false)
     *
     * @return int The written bytes
     */
    public function write($content, $lock=false)
    {
        return $this->filesystem->write((string)$this->url, $content, $lock, $this->resource());
    }

    /**
     * @return self
     */
    public function open()
    {
        $this->resource();
        return $this;
    }

    /**
     * @return self
     */
    public function close()
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return (bool)$this->resource;
    }

    /**
     * @return resource
     */
    public function resource()
    {
        if (!$this->resource) {
            $this->resource = $this->filesystem->handle((string)$this->url);
        }
        return $this->resource;
    }

    /**
     * @return UrlContract
     */
    public function url()
    {
        return $this->url;
    }
}
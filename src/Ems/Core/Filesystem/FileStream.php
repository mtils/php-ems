<?php
/**
 *  * Created by mtils on 21.10.18 at 08:48.
 **/

namespace Ems\Core\Filesystem;


use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Core\Url;
use function file_get_contents;

/**
 * Class FileStream
 *
 * A file stream represents a call of fopen() and the following fread, fclose..
 * It can be used for one file (handle) with one configuration (read/write/lock)
 * and must be recreated for actions with a different configuration.
 *
 * @package Ems\Core\Filesystem
 */
class FileStream extends AbstractStream
{
    /**
     * @var UrlContract
     */
    protected $url;

    /**
     * @var string
     */
    protected $mode = 'r+';

    /**
     * FileStream constructor.
     *
     * @param UrlContract|string $url
     * @param string             $mode  (default:'r+')
     * @param bool               $lock (default:false)
     */
    public function __construct($url, $mode='r+', $lock=false)
    {
        $this->url = $url instanceof UrlContract ? $url : new Url("$url");
        $this->mode = $mode;
        $this->shouldLock = $lock;
    }

    /**
     * @return UrlContract
     */
    public function url()
    {
        return $this->url;
    }

    /**
     * @return resource
     */
    public function resource()
    {
        if (!$this->resource) {
            $this->assignResource($this->createHandle((string)$this->url));
        }
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|string
     */
    protected function readAll()
    {
        return file_get_contents((string)$this->url);
    }

    /**
     * @param string $filePath
     *
     * @return resource
     **/
    protected function createHandle($filePath)
    {
        if (!$handle = $this->openHandle($filePath)) {
            throw new ResourceNotFoundException("Path '$filePath' cannot be opened");
        }

        $this->applySettings($handle);

        return $handle;
    }

    /**
     * @param string $path
     *
     * @return bool|resource
     */
    protected function openHandle($path)
    {
        return @fopen($path, $this->mode);
    }
}
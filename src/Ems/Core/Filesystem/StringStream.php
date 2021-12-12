<?php
/**
 *  * Created by mtils on 22.06.19 at 09:37.
 **/

namespace Ems\Core\Filesystem;


use Countable;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Url;
use function base64_encode;
use function is_resource;
use function stream_get_contents;

/**
 * Class StringStream
 *
 * Use any string as a stream.
 *
 * @package Ems\Core\Filesystem
 */
class StringStream extends AbstractStream implements Countable
{
    /**
     * @var string|Stringable
     */
    protected $string;

    /**
     * @var UrlContract
     */
    protected $url;

    /**
     * @var bool
     */
    protected $isOwnRewind = false;

    /**
     * @param string|Stringable $string
     * @param $mode
     */
    public function __construct($string, $mode='r+')
    {
        $this->string = $string;
        $this->mode = $mode;
        $this->url = new Url('php://memory');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function type()
    {
        if (is_resource($this->resource)) {
            return parent::type();
        }
        return 'stream';
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
        if (!$this->resource && $this->string !== null) {
            $this->assignResource($this->createHandle());
        }
        return $this->resource;
    }

    /**
     * {@inheritDoc}
     *
     * @return int The custom count as an integer.
     *
     * @since 5.1.0
     */
    public function count()
    {
        return is_string($this->string) ? strlen($this->string) : 0;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $data
     *
     * @return bool (For compatibility no int for written bytes)
     */
    public function write($data)
    {
        if (!parent::write($data)) {
            return false;
        }

        // This is little bit overhead but I didn't find any other simple
        // solution (including support for all writing modes)
        $resource = $this->resource();
        $position = ftell($resource);
        $this->isOwnRewind = true;
        $this->rewind();
        $this->isOwnRewind = false;
        $this->string = stream_get_contents($resource);
        fseek($resource, $position);
        return true;
    }

    /**
     * @return string|Stringable
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * Re-implement this method to allow fast toString/complete reading.
     *
     * @return string
     */
    protected function readAll()
    {
        return $this->string !== null ? (string)$this->string : '';
    }

    /**
     * @return bool|resource
     */
    protected function createHandle()
    {
        return fopen("data://text/plain;base64,".$this->encodeData($this->string), $this->mode);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function encodeData($string)
    {
        return base64_encode($string);
    }

    /**
     * Overwritten to allow rewind in write only streams if it is caused by me.
     */
    protected function failOnWriteOnly()
    {
        if ($this->isOwnRewind) {
            return;
        }
        parent::failOnWriteOnly();
    }


}
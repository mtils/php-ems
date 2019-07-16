<?php
/**
 *  * Created by mtils on 15.07.19 at 15:02.
 **/

namespace Ems\Core;

use Ems\Contracts\Core\Response as ResponseContract;
use Ems\Contracts\Core\StringableTrait;
use Ems\Core\Support\BootingArrayData;
use function count;
use ArrayIterator;

class Response implements ResponseContract
{
    use BootingArrayData;
    use StringableTrait;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var mixed
     */
    protected $payload;

    /**
     * @var bool
     */
    protected $payloadCreated = false;

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function contentType()
    {
        if (!$this->contentType) {
            return ManualMimeTypeProvider::$fallbackMimeType;
        }
        return $this->contentType;
    }

    /**
     * Explicitly set the content type. Otherwise it gets detected from headers.
     *
     * @param $contentType
     *
     * @return self
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return mixed
     */
    public function payload()
    {
        if (!$this->payloadCreated) {
            $this->payload = $this->createPayload();
            $this->payloadCreated = true;
        }
        return $this->payload;
    }

    /**
     * Manually set the payload.
     *
     * @param $payload
     *
     * @return $this
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
        $this->payloadCreated = true;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIterator()
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return count($this->toArray());
    }

    /**
     * Return the response body.
     *
     * @return string
     */
    public function toString()
    {
        return (string)$this->payload();
    }

    /**
     * Try to create the payload out of the assigned body.
     *
     * @return mixed
     */
    protected function createPayload()
    {
        return '';
    }

}
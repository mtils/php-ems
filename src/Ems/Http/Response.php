<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 18:13
 */

namespace Ems\Http;

use Ems\Contracts\Core\Arrayable;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Http\Response as ResponseContract;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Serializer\JsonSerializer;
use Ems\Core\Support\BootingArrayData;
use Ems\Core\Support\StringableTrait;
use function is_string;
use Traversable;
use UnexpectedValueException;

class Response implements ResponseContract
{
    use BootingArrayData;
    use StringableTrait;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var string
     */
    protected $body;

    /**
     * @var string
     */
    protected $rawDocument;

    /**
     * @var mixed
     */
    protected $payload;

    /**
     * @var bool
     */
    protected $payloadCreated = false;

    /**
     * @var bool
     */
    protected $bodyRendered = false;

    /**
     * @var SerializerContract
     */
    protected $serializer;

    /**
     * Response constructor.
     *
     * @param array  $headers (optional)
     * @param string $body (optional)
     */
    public function __construct(array $headers=[], $body='')
    {
        $this->headers = $headers;

        if ($this->contentType() == 'application/json') {
            $this->serializer = new JsonSerializer();
            $this->serializer->asArrayByDefault(true);
        }

        if ($body) {
            $this->setBody($body);
        }

    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function status()
    {
        if (!$this->status) {
            return $this->findStatusInHeaders($this->headers());
        }
        return $this->status;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function contentType()
    {
        if (!$this->contentType) {
            return $this->findContentTypeInHeaders($this->headers());
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
     * Set the HTTP status
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Core\Content
     */
    public function content()
    {
        throw new NotImplementedException('This method is not implemented now.');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function body()
    {
        if (!$this->bodyRendered) {
            $this->body = $this->renderBody();
            $this->bodyRendered = true;
        }
        return $this->body;
    }

    /**
     * Set the body
     *
     * @param string $body
     *
     * @return self
     */
    public function setBody($body)
    {
        $this->body = "$body";
        $this->bodyRendered = true;
        return $this;
    }

    /**
     * @return mixed
     */
    public function raw()
    {
        return $this->rawDocument;
    }

    /**
     * Set the raw complete response document with header and body.
     *
     * @param string $raw
     *
     * @return self
     */
    public function setRaw($raw)
    {
        $this->rawDocument = $raw;
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
        return new \ArrayIterator($this->toArray());
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return count($this->toArray());
    }

    /**
     * Return the assigned serializer.
     *
     * @return SerializerContract
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Set the serializer to serialize/deserialize the payload.
     *
     * @param SerializerContract $serializer
     *
     * @return $this
     */
    public function setSerializer(SerializerContract $serializer)
    {
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * Return the response body.
     *
     * @return string
     */
    public function toString()
    {
        return (string)$this->body;
    }


    /**
     * Fills the array when accessing it.
     */
    protected function fillOnce()
    {
        $payload = $this->payload();

        if (is_array($payload)) {
            return $this->fillAttributes($payload);
        }

        if ($payload instanceof Arrayable) {
            return $this->fillAttributes($payload->toArray());
        }

        if ($payload instanceof \Traversable) {
            return $this->fillAttributes(iterator_to_array($payload));
        }

        if ($payload instanceof \stdClass) {
            return $this->fillAttributes(json_decode(json_encode($payload), true));
        }

        return $this->fillAttributes([]);
    }

    /**
     * Tries to find the http status code in its header.
     *
     * @param $headers
     *
     * @return int
     */
    protected function findStatusInHeaders($headers)
    {
        if (!isset($headers[0]) || strpos($headers[0], 'HTTP/') !== 0) {
            throw new UnexpectedValueException('Invalid HTTP Headers, missing status line');
        }

        $parts = explode(' ', trim($headers[0]));

        return (int)trim($parts[1]);
    }

    /**
     * Tries to find the content type in the assigned headers.
     *
     * @param $headers
     *
     * @return string
     */
    protected function findContentTypeInHeaders($headers)
    {

        foreach ($headers as $header) {

            $lower = trim(strtolower($header));

            if (strpos($lower, 'content-type:') !== 0) {
                continue;
            }

            $parts = explode(';', $header);

            $keyAndValue = explode(': ', trim($parts[0]), 2);

            if (!isset($keyAndValue[1])) {
                continue;
            }

            return trim($keyAndValue[1]);

        }

        return '';
    }

    /**
     * Try to create the payload out of the assigned body.
     *
     * @return mixed
     */
    protected function createPayload()
    {
        if (!$this->body) {
            return null;
        }

        if (!$this->serializer) {
            throw new UnConfiguredException('No serializer assigned, cannot build the payload.');
        }

        return $this->serializer->deserialize($this->body);
    }

    /**
     * Try to create the body out of the assigned payload.
     *
     * @return string
     */
    protected function renderBody()
    {
        if (is_scalar($this->payload) || $this->payload === null) {
            return (string)$this->payload;
        }

        if ($this->payload instanceof Stringable) {
            return $this->payload->toString();
        }

        if (is_object($this->payload) && method_exists($this->payload, '__toString')) {
            return (string)$this->payload;
        }

        if (!$this->serializer) {
            throw new UnConfiguredException('No serializer assigned, cannot build the body.');
        }

        return $this->serializer->serialize($this->payload);

    }
}
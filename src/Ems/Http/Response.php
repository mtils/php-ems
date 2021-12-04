<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 18:13
 */

namespace Ems\Http;

use Ems\Contracts\Core\Arrayable;
use Ems\Contracts\Core\Content;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Http\Response as ResponseContract;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Response as CoreResponse;
use Ems\Core\Serializer\JsonSerializer;
use stdClass;
use Traversable;
use UnexpectedValueException;

use function explode;
use function strpos;
use function trim;

class Response extends CoreResponse implements ResponseContract
{
    /**
     * @var int
     */
    protected $status = 200;

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
        $this->setHeaders($headers);

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
    public function status() : int
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
     * {@inheritdoc}
     *
     * @return array
     */
    public function headers()
    {
        $headers = [];
        if ($contentType = $this->contentType) {
            $headers[] = "Content-Type:$contentType";
        }
        foreach ($this->headers as $line) {
            if ($contentType && strpos($line, 'Content-Type') === 0) {
                continue;
            }
            $headers[] = $line;
        }
        return $headers;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers) : Response
    {
        $this->headers = $headers;
        foreach ($headers as $header) {
            if ($this->isStatusHeaderLine($header)) {
                $this->setStatus($this->getStatusFromHeaderLine($header));
                break;
            }
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return Content
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

        if ($payload instanceof Traversable) {
            return $this->fillAttributes(iterator_to_array($payload));
        }

        if ($payload instanceof stdClass) {
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
    protected function findStatusInHeaders($headers) : int
    {
        if (!isset($headers[0]) || !$this->isStatusHeaderLine($headers[0])) {
            throw new UnexpectedValueException('Invalid HTTP Headers, missing status line');
        }
        return $this->getStatusFromHeaderLine($headers[0]);
    }

    /**
     * @param string $statusLine
     * @return int
     */
    protected function getStatusFromHeaderLine(string $statusLine) : int
    {
        $parts = explode(' ', trim($statusLine));

        return (int)trim($parts[1]);
    }

    /**
     * @param string $headerLine
     * @return bool
     */
    protected function isStatusHeaderLine(string $headerLine) : bool
    {
        return strpos($headerLine, 'HTTP/') === 0;
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
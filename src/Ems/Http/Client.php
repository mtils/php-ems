<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 11.11.17
 * Time: 11:01
 */

namespace Ems\Http;

use Ems\Contracts\Core\ConnectionPool;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Http\Client as ClientContract;
use Ems\Contracts\Http\Connection as HttpConnection;
use Ems\Contracts\Http\Response;
use Ems\Core\Exceptions\MisConfiguredException;
use Ems\Core\Serializer\JsonSerializer;
use Ems\Http\Serializer\UrlEncodeSerializer;
use UnexpectedValueException;


class Client implements ClientContract
{

    /**
     * @var ConnectionPool
     */
    protected $connections;

    /**
     * @var SerializerContract
     */
    protected $serializer;

    /**
     * @var UrlEncodeSerializer
     */
    protected $formSerializer;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Client constructor.
     *
     * @param ConnectionPool     $connections
     * @param SerializerContract $serializer
     * @param array              $headers (optional)
     */
    public function __construct(ConnectionPool $connections,
                                SerializerContract $serializer=null, $headers=[])
    {
        $this->connections = $connections;
        $this->serializer = $serializer ?: new JsonSerializer();
        $this->formSerializer = new UrlEncodeSerializer();
        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     *
     * @param UrlContract  $url
     *
     * @return Response
     */
    public function head(UrlContract $url)
    {
        return $this->configure($this->con($url)->send('HEAD'));
    }

    /**
     * @inheritDoc
     *
     * @param UrlContract  $url
     * @param null $contentType
     *
     * @return Response
     */
    public function get(UrlContract $url, $contentType = null)
    {
        $headers = $this->buildHeaders($contentType);
        $response = $this->con($url)->send('GET', $headers);
        return $this->configure($response, $contentType);
    }

    /**
     * @inheritDoc
     *
     * @param UrlContract $url
     * @param mixed       $data
     * @param string      $contentType (optional)
     *
     * @return Response
     */
    public function post(UrlContract $url, $data = null, $contentType = null)
    {
        $headers = $this->buildHeaders($contentType);
        $data = $this->serializeIfNeeded($data, $contentType);
        $response = $this->con($url)->send('POST', $headers, $data);
        return $this->configure($response, $contentType);
    }

    /**
     * @inheritDoc
     *
     * @param UrlContract $url
     * @param mixed       $data
     * @param string      $contentType (optional)
     *
     * @return Response
     */
    public function put(UrlContract $url, $data = null, $contentType = null)
    {
        $headers = $this->buildHeaders($contentType);
        $data = $this->serializeIfNeeded($data, $contentType);
        $response = $this->con($url)->send('PUT', $headers, $data);
        return $this->configure($response, $contentType);
    }

    /**
     * @inheritDoc
     *
     * @param UrlContract $url
     * @param mixed       $data
     * @param string      $contentType (optional)
     *
     * @return Response
     */
    public function patch(UrlContract $url, $data = null, $contentType = null)
    {
        $headers = $this->buildHeaders($contentType);
        $data = $this->serializeIfNeeded($data, $contentType);
        $response = $this->con($url)->send('PATCH', $headers, $data);
        return $this->configure($response, $contentType);
    }

    /**
     * @inheritDoc
     *
     * @param UrlContract $url
     * @param mixed       $data
     * @param string      $contentType (optional)
     *
     * @return Response
     */
    public function delete(UrlContract $url, $data = null, $contentType = null)
    {
        $headers = $this->buildHeaders($contentType);
        $data = $this->serializeIfNeeded($data, $contentType);
        $response = $this->con($url)->send('DELETE', $headers, $data);
        return $this->configure($response, $contentType);
    }

    /**
     * @inheritDoc
     *
     * @param UrlContract    $url
     * @param array  $data
     * @param string $method (default: POST)
     *
     * @return Response
     */
    public function submit(UrlContract $url, array $data, $method=HttpConnection::POST)
    {
        $headers = $this->buildHeaders($this->serializer->mimeType());
        $headers[] = 'Content-Type: ' . $this->formSerializer->mimeType();
        $data = $this->formSerializer->serialize($data);
        $response = $this->con($url)->send($method, $headers, $data);
        return $this->configure($response, $this->formSerializer->mimeType());
    }

    /**
     * @inheritDoc
     *
     * @param string|array $header
     * @param string       $value (optional)
     *
     * @return self
     *
     * @example Client::headers()
     */
    public function header($header, $value = null)
    {
        if (!is_array($header)) {
            return $this->header(["$header: $value"]);
        }
        return new static($this->connections, $this->serializer, $header);
    }

    /**
     * Return guaranteed a HttpConnection.
     *
     * @param UrlContract $url
     *
     * @return HttpConnection
     */
    protected function con(UrlContract $url)
    {

        $con = $this->connections->connection($url);

        if (!$con instanceof HttpConnection) {
            throw new UnexpectedValueException("The connection returned for $url is no HttpConnection");
        }

        return $con;

    }

    /**
     * Build the headers for the connection.
     *
     * @param $contentType
     *
     * @return array
     */
    protected function buildHeaders($contentType)
    {
        $headers = $this->headers;
        if ($contentType) {
            $headers[] = "Accept: $contentType";
        }
        return $headers;
    }

    /**
     * Configure the response. Just set the right serializer.
     *
     * @param Response $response
     * @param null $contentType
     *
     * @return Response
     */
    protected function configure(Response $response, $contentType=null)
    {
        if (!$contentType) {
            return $response;
        }

        if (strtolower($contentType) == strtolower($this->serializer->mimeType())) {
            $response->setSerializer($this->serializer);
        }

        return $response;
    }

    /**
     * Serialize the data before sending it. (If it is not a string)
     *
     * @param $data
     * @param $contentType
     *
     * @return string
     */
    protected function serializeIfNeeded($data, $contentType)
    {

        if ($data instanceof Stringable) {
            return $data->toString();
        }

        if (is_scalar($data) || is_null($data) || is_object($data) && method_exists($data, '__toString')) {
            return "$data";
        }

        if (strtolower($contentType) != strtolower($this->serializer->mimeType())) {
            throw new MisConfiguredException("The assigned Serializer does not support $contentType");
        }

        return $this->serializer->serialize($data);
    }

}
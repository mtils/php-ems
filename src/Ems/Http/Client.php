<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 11.11.17
 * Time: 11:01
 */

namespace Ems\Http;

use Closure;
use Ems\Contracts\Core\ConnectionPool;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Http\Client as ClientContract;
use Ems\Contracts\Http\Connection as HttpConnection;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Exceptions\MisConfiguredException;
use Ems\Core\Serializer;
use Ems\Core\Serializer\JsonSerializer;
use Ems\Http\Serializer\UrlEncodeSerializer;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

use function strtolower;


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
     * @var string
     */
    protected $defaultAcceptedContentType = 'application/json';

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
        $this->serializer = $serializer;
        $this->formSerializer = new UrlEncodeSerializer();
        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     *
     * @param UrlContract  $url
     *
     * @return HttpResponse
     */
    public function head(UrlContract $url) : ResponseInterface
    {
        return $this->configure($this->con($url)->send('HEAD'));
    }

    /**
     * @inheritDoc
     *
     * @param UrlContract  $url
     * @param null $contentType
     *
     * @return HttpResponse
     */
    public function get(UrlContract $url, $contentType = null) : ResponseInterface
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
     * @return HttpResponse
     */
    public function post(UrlContract $url, $data = null, string $contentType = null) : ResponseInterface
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
     * @return HttpResponse
     */
    public function put(UrlContract $url, $data = null, string $contentType = null) : ResponseInterface
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
     * @return HttpResponse
     */
    public function patch(UrlContract $url, $data = null, string $contentType = null) : ResponseInterface
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
     * @return HttpResponse
     */
    public function delete(UrlContract $url, $data = null, string $contentType = null) : ResponseInterface
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
     * @return HttpResponse
     */
    public function submit(UrlContract $url, array $data, string $method=HttpConnection::POST) : ResponseInterface
    {

        $headers = $this->buildHeaders($this->getAcceptedContentType());
        $headers[] = 'Content-Type: ' . $this->formSerializer->mimeType();
        $data = $this->formSerializer->serialize($data);
        $response = $this->con($url)->send($method, $headers, $data);
        // TODO The response should be according accepted content type not the sent one
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
    public function header($header, string $value = null) : ClientContract
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
     * @param HttpResponse $response
     * @param null $contentType
     *
     * @return HttpResponse
     */
    protected function configure(HttpResponse $response, $contentType=null) : HttpResponse
    {
        if (!$contentType) {
            return $response;
        }

        $response->provideSerializerBy($this->serializerFactory());

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

        $serializer = $this->serializerFactory()($contentType);

        return $serializer->serialize($data);
    }

    protected function serializerFactory() : Closure
    {
        if (!$this->serializer) {
            return self::makeSerializerFactory();
        }

        if ($this->serializer instanceof Serializer) {
            return function ($contentType) {
                return $this->serializer->forMimeType($contentType);
            };
        }

        return function ($contentType) {
            if (strtolower($contentType) == strtolower($this->serializer->mimeType())) {
                return $this->serializer;
            }
            throw new HandlerNotFoundException("No serializer found for '$contentType'");
        };
    }

    /**
     * TODO Centralize this. Perhaps use ResponseFactory everywhere
     * @return Closure
     */
    public static function makeSerializerFactory() : Closure
    {
        return function ($contentType) {
            $contentType = strtolower($contentType);
            if ($contentType == 'application/json') {
                return (new JsonSerializer())->asArrayByDefault();
            }
            if ($contentType == 'application/vnd.php.serialized') {
                return new Serializer();
            }
            if ($contentType == 'application/x-www-form-urlencoded') {
                return new UrlEncodeSerializer();
            }
            throw new HandlerNotFoundException("No Serializer found for '$contentType'");
        };
    }

    /**
     * Return the default accepted content type when sending requests.
     *
     * @return string
     */
    protected function getAcceptedContentType() : string
    {
        if (!$this->serializer) {
            return $this->defaultAcceptedContentType;
        }
        if ($this->serializer instanceof Serializer) {
            return $this->defaultAcceptedContentType;
        }
        return $this->serializer->mimeType();
    }
}
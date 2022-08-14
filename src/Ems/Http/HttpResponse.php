<?php
/**
 *  * Created by mtils on 19.12.2021 at 14:02.
 **/

namespace Ems\Http;

use DateTime;
use Ems\Contracts\Core\Arrayable;
use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Message;
use Ems\Contracts\Core\Serializer;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Http\Cookie;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Filesystem\AbstractStream;
use Ems\Core\Filesystem\StringStream;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Core\Response;
use Ems\Http\Psr\PsrMessageTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use stdClass;
use Traversable;
use UnexpectedValueException;

use function call_user_func;
use function explode;
use function func_num_args;
use function is_numeric;
use function iterator_to_array;
use function strpos;
use function strtolower;
use function trim;

/**
 * @property-read string protocolVersion
 * @property-read array headers
 * @property-read StreamInterface body
 * @property-read string raw The raw http request string with headers and body
 * @property-read array|Cookie[] cookies
 * @property-read bool secureCookies
 */
class HttpResponse extends Response implements ResponseInterface
{
    use PsrMessageTrait;

    /**
     * @var Serializer|null
     */
    protected $serializer;

    /**
     * @var callable|null
     */
    protected $serializerFactory;

    /**
     * @var bool
     */
    protected $payloadDeserialized = false;

    /**
     * @var string
     */
    protected $raw;

    /**
     * @var array|Cookie[]
     */
    protected $cookies = [];

    /**
     * @var bool
     */
    protected $secureCookies = true;

    public function __construct($data = [], array $headers=[], int $status=200)
    {
        $this->transport = Message::TRANSPORT_NETWORK;
        $this->contentType = 'text/html';

        if (!func_num_args()) {
            parent::__construct();
            $this->status = $status;
            return;
        }

        if (func_num_args() > 1) {
            parent::__construct($data, $headers, $status);
            return;
        }

        $dataIsArray = is_array($data);

        if ($dataIsArray && isset($data['headers'])) {
            $data['envelope'] = $data['headers'];
        }

        parent::__construct($data);

        if ($dataIsArray && !isset($data['status'])) {
            $this->status = $status;
        }
        if ($this->status == 0) {
            $this->status = 200;
        }
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->statusMessage;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        switch ($key) {
            case 'protocolVersion':
                return $this->protocolVersion;
            case 'body':
                return $this->getBody();
            case 'headers':
                return $this->envelope;
            case 'raw':
                return $this->raw;
            case 'cookies':
                return $this->cookies;
            case 'secureCookies':
                return $this->secureCookies;
        }
        return parent::__get($key);
    }

    /**
     * @return StreamInterface|AbstractStream
     */
    public function getBody() : StreamInterface
    {
        if ($this->payload instanceof StreamInterface) {
            return $this->payload;
        }
        return new StringStream($this->toString());
    }


    public function toString()
    {
        if (Type::isStringable($this->payload)) {
            return (string)$this->payload;
        }
        return $this->serializePayload($this->payload);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $this->tryDeserializePayload();
        return parent::toArray();
    }

    /**
     * @param $id
     * @param $default
     * @return mixed|null
     */
    public function get($id, $default = null)
    {
        $this->tryDeserializePayload();
        return parent::get($id, $default);
    }


    /**
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        $this->tryDeserializePayload();
        return parent::offsetExists($offset);
    }

    /**
     * @param $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->tryDeserializePayload();
        return parent::offsetGet($offset);
    }

    /**
     * @param $offset
     * @param $value
     */
    public function offsetSet($offset, $value)
    {
        $this->tryDeserializePayload();
        parent::offsetSet($offset, $value);
    }

    /**
     * @param $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->tryDeserializePayload();
        parent::offsetUnset($offset);
    }

    /**
     * @param string|Cookie $cookie
     * @param string|null   $value
     * @param int|DateTime  $expire
     * @param string        $path
     * @param string|null   $domain
     * @param bool|null     $secure
     * @param bool          $httpOnly
     * @param string        $sameSite
     * @return self
     */
    public function withCookie($cookie, string $value=null, $expire=null, string $path='/', string $domain=null, bool $secure=null, bool $httpOnly=true, string $sameSite=Cookie::LAX) : HttpResponse
    {
        $secure = $secure === null ? $this->secureCookies : $secure;
        $cookie = $cookie instanceof Cookie ? $cookie : new Cookie($cookie, $value, $expire, $path, $domain, $secure, $httpOnly, $sameSite);
        $cookies = $this->cookies;
        $cookies[$cookie->name] = $cookie;
        return $this->replicate(['cookies' => $cookies]);
    }

    /**
     * @param string|Cookie $cookie
     * @return self
     */
    public function withoutCookie($cookie) : HttpResponse
    {
        $name = $cookie instanceof Cookie ? $cookie->name : $cookie;
        $cookies = $this->cookies;
        if (isset($cookies[$name])) {
            unset($cookies[$name]);
        }
        return $this->replicate(['cookies' => $cookies]);
    }

    /**
     * Set the default for created cookies.
     *
     * @param bool $secure
     * @return HttpResponse
     */
    public function withSecureCookies(bool $secure) : HttpResponse
    {
        if (!$this->cookies) {
            return $this->replicate(['secureCookies' => $secure]);
        }
        $cookies = [];
        foreach ($this->cookies as $cookie) {
            $clone = clone $cookie;
            $clone->secure = $secure;
            $cookies[$cookie->name] = $clone;
        }
        return $this->replicate(['secureCookies' => $secure, 'cookies' => $cookies]);
    }

    /**
     * Assign a callable that creates serializers to serialize and deserialize
     * data.
     *
     * @param callable $callable
     * @return void
     */
    public function provideSerializerBy(callable $callable)
    {
        $this->serializerFactory = $callable;
    }

    protected function apply(array $attributes)
    {
        if (isset($attributes['raw'])) {
            $this->raw = $attributes['raw'];
        }
        if (isset($attributes['protocolVersion'])) {
            $this->protocolVersion = $attributes['protocolVersion'];
        }
        if (isset($attributes['serializerFactory'])) {
            $this->serializerFactory = $attributes['serializerFactory'];
        }
        if (isset($attributes['cookies'])) {
            $this->cookies = $attributes['cookies'];
        }
        if (isset($attributes['secureCookies'])) {
            $this->secureCookies = $attributes['secureCookies'];
        }
        parent::apply($attributes);
    }

    protected function copyStateInto(array &$attributes)
    {
        if (!isset($attributes['raw'])) {
            $attributes['raw'] = $this->raw;
        }
        if (!isset($attributes['protocolVersion'])) {
            $attributes['protocolVersion'] = $this->protocolVersion;
        }
        if (!isset($attributes['cookies'])) {
            $attributes['cookies'] = $this->cookies;
        }
        if (!isset($attributes['secureCookies'])) {
            $attributes['secureCookies'] = $this->secureCookies;
        }
        $attributes['serializerFactory'] = $this->serializerFactory;
        parent::copyStateInto($attributes);
    }


    protected function applyEnvelope(array $envelope)
    {
        $this->envelope = [];
        foreach ($envelope as $key=>$value) {
            if (!is_numeric($key)) {
                $this->applyEnvelopeValue($key, $value);
                continue;
            }
            if (!$this->isStatusHeaderLine($value)) {
                $keyAndValue = explode(':', trim($value), 2);
                $this->applyEnvelopeValue(trim($keyAndValue[0]), trim($keyAndValue[1]));
                continue;
            }
            $this->status = $this->getStatusFromHeaderLine($value);
            $this->protocolVersion = $this->getProtocolVersionFromHeaderLine($value);
        }
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
     * @param string $statusLine
     * @return int
     */
    protected function getStatusFromHeaderLine(string $statusLine) : int
    {
        $parts = explode(' ', trim($statusLine));
        return (int)trim($parts[1]);
    }

    /**
     * @param string $statusLine
     * @return string
     */
    protected function getProtocolVersionFromHeaderLine(string $statusLine) : string
    {
        $parts = explode('/', trim($statusLine), 2);
        return trim(explode(' ', $parts[1])[0]);
    }

    protected function applyEnvelopeValue($key, $value)
    {
        $lowerKey = strtolower($key);
        $this->envelope[$key] = $value;
        if ($lowerKey == 'content-type') {
            $this->contentType = $value;
        }
    }

    /**
     * @param mixed $payload
     * @return string
     */
    protected function serializePayload($payload) : string
    {
        if (!$serializer = $this->createSerializer($this->contentType)) {
            throw new UnConfiguredException('You try to convert an array into string but didnt assign a serializer to handle ' . $this->contentType);
        }
        return $serializer->serialize($payload);
    }

    /**
     * @param string $contentType
     * @return Serializer|null
     */
    protected function createSerializer(string $contentType)
    {
        if (!$this->serializerFactory) {
            return null;
        }
        $serializer = call_user_func($this->serializerFactory, $contentType);
        if ($serializer instanceof Serializer) {
            return $serializer;
        }
        throw new UnexpectedValueException("The assigned serializer factory has to create instance of " . Serializer::class . ' not ' . Type::of($serializer));
    }

    protected function tryDeserializePayload()
    {
        if ($this->payloadDeserialized || !$this->payload || $this->custom) {
            return;
        }
        if (!$serializer = $this->createSerializer($this->contentType)) {
            throw new UnConfiguredException('You try to deserialize a string into data but didnt assign a deserializer for ' . $this->contentType);
        }
        $data = $serializer->deserialize($this->payload);
        $this->custom = $this->castDeserializedToArray($serializer->deserialize($this->payload));
        $this->payloadDeserialized = true;
    }

    /**
     * @param $payload
     * @return array
     */
    protected function castDeserializedToArray($payload) : array
    {
        if (is_array($payload)) {
            return $payload;
        }
        if ($payload instanceof stdClass) {
            return (array)$payload;
        }
        if ($payload instanceof Arrayable) {
            return $payload->toArray();
        }
        if ($payload instanceof Traversable) {
            return iterator_to_array($payload);
        }

        throw new TypeException('Cannot cast deserialized data ito array, its: ' . Type::of($payload));
    }
}
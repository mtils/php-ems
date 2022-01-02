<?php
/**
 *  * Created by mtils on 27.12.2021 at 16:28.
 **/

namespace Ems\Http;

use Ems\Contracts\Core\Message;
use Ems\Contracts\Routing\Input;
use Ems\Core\ImmutableMessage;
use Ems\Core\Url;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Http\Psr\PsrMessageTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

use function is_array;

/**
 * @property-read string protocolVersion
 * @property-read array headers
 * @property-read StreamInterface body
 * @property-read string requestTarget
 * @property-read string method
 * @property-read UriInterface|UrlContract uri
 * @property-read UriInterface|UrlContract url
 */
class HttpRequest extends ImmutableMessage implements RequestInterface
{
    use PsrMessageTrait;

    /**
     * @var string
     */
    protected $requestTarget = '';

    /**
     * @var string
     */
    protected $method = Input::GET;

    /**
     * @var UriInterface
     */
    protected $uri;

    public function __construct($data = [], array $headers=[], Url $url=null)
    {
        $this->transport = Message::TRANSPORT_NETWORK;
        $this->uri = $url ?: new Url();

        if ($headers || !is_array($data)) {
            $data = [
                'payload' => $data,
                'envelope' => $headers
            ];
        }

        if (is_array($data) && isset($data['headers'])) {
            $data['envelope'] = $data['headers'];
        }

        if (isset($data['payload']) && is_array($data['payload']) && !isset($data['custom'])) {
            $data['custom'] = $data['payload'];
        }
        parent::__construct($data);
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
            case 'requestTarget':
                return $this->getRequestTarget();
            case 'method':
                return $this->getMethod();
            case 'uri':
            case 'url':
                return $this->getUri();
        }
        return parent::__get($key);
    }

    /**
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }
        $path = $this->uri->getPath();
        $path = $path === '' ? '/' : $path;
        $query = $this->uri->getQuery();
        $this->requestTarget = $path . ($query ? "?$query" : '');
        return $this->requestTarget;
    }

    /**
     * @param string $requestTarget
     * @return $this
     */
    public function withRequestTarget($requestTarget)
    {
        return $this->replicate(['requestTarget' => $requestTarget]);
    }

    /**
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * @param $method
     * @return ImmutableMessage|HttpRequest
     */
    public function withMethod($method)
    {
        return $this->replicate(['method' => $method]);
    }

    public function getUri()
    {
        if ($this->uri) {
            return $this->uri;
        }
        return new Url();
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return $this->replicate(['uri' => $uri, 'preserveHost' => $preserveHost]);
    }

    protected function apply(array $attributes)
    {
        if (isset($attributes['url']) && !isset($attributes['uri'])) {
            $attributes['uri'] = $attributes['url'];
            unset($attributes['url']);
        }
        if (isset($attributes['protocolVersion'])) {
            $this->protocolVersion = $attributes['protocolVersion'];
        }
        if (isset($attributes['requestTarget'])) {
            $this->requestTarget = $attributes['requestTarget'];
        }
        if (isset($attributes['method'])) {
            $this->method = $attributes['method'];
        }
        if (isset($attributes['uri']) && $attributes['uri'] instanceof UrlContract) {
            $this->uri = $attributes['uri'];
        }
        parent::apply($attributes);
    }

    protected function copyStateInto(array &$attributes)
    {

        if (!isset($attributes['protocolVersion'])) {
            $attributes['protocolVersion'] = $this->protocolVersion;
        }

        if (!isset($attributes['requestTarget'])) {
            $attributes['requestTarget'] = $this->requestTarget;
        }

        if (!isset($attributes['method'])) {
            $attributes['method'] = $this->method;
        }

        if (!isset($attributes['uri'])) { //} && !isset($attributes['url'])) {
            $attributes['uri'] = $this->uri;
        }

        parent::copyStateInto($attributes);
    }

}
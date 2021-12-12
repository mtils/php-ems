<?php
/**
 *  * Created by mtils on 12.12.2021 at 08:22.
 **/

namespace Ems\Contracts\Routing;

use Ems\Contracts\Core\AbstractMessage;
use Ems\Contracts\Core\Message;
use Ems\Contracts\Core\Stringable;
use Ems\Core\Filesystem\AbstractStream;
use Ems\Core\Filesystem\StringStream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

use function explode;
use function func_get_args;
use function is_array;
use function is_scalar;
use function method_exists;
use function strtolower;

/**
 * @property-read TraceableMessage|null previous
 * @property-read TraceableMessage|null next
 * @property-read string protocolVersion
 * @property-read array headers
 * @property-read StreamInterface body
 */
class TraceableMessage extends AbstractMessage implements MessageInterface
{

    /**
     * @var TraceableMessage|null
     */
    protected $previous;

    /**
     * @var TraceableMessage|null
     */
    protected $next;

    /**
     * @var string
     */
    protected $protocolVersion = '';

    public function __construct(array $attributes = [])
    {
        parent::__construct();
        $this->apply($attributes);
    }

    /**
     * Return a new instance that has $key set to $value. Pass an array for
     * multiple changed parameters.
     *
     * @param string|array $key
     * @param mixed $value (optional)
     * @return $this
     */
    public function with($key, $value=null) : TraceableMessage
    {
        $attributes = is_array($key) ? $key : [$key => $value];
        $custom = $this->custom;
        foreach ($attributes as $key=>$value) {
            $custom[$key] = $value;
        }
        return $this->replicate([Message::POOL_CUSTOM => $custom]);
    }

    /**
     * Return a new instance without data for $key(s)
     *
     * @param string|array $key
     * @return $this
     */
    public function without($key) : TraceableMessage
    {
        $keys = is_array($key) ? $key : func_get_args();
        $custom = $this->custom;
        foreach ($keys as $key) {
            unset($custom[$key]);
        }
        return $this->replicate([Message::POOL_CUSTOM => $custom]);
    }

    /**
     * @return string
     */
    public function getProtocolVersion() : string
    {
        if (!$this->protocolVersion) {
            $this->protocolVersion = $_SERVER['SERVER_PROTOCOL'] ?? '';
        }
        return $this->protocolVersion;
    }

    /**
     * @param $version
     * @return $this
     */
    public function withProtocolVersion($version) : TraceableMessage
    {
        return $this->replicate(['protocolVersion' => $version]);
    }

    /**
     * @return string[][]
     */
    public function getHeaders() : array
    {
        $headers = [];
        foreach ($this->envelope as $key=>$value) {
            $headers[$key] = explode(',', $value);
        }
        return $headers;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasHeader($name) : bool
    {
        $lowerName = strtolower($name);
        foreach ($this->envelope as $key=>$value) {
            if (strtolower($key) == $lowerName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $name
     * @return string[]
     */
    public function getHeader($name) : array
    {
        foreach ($this->envelope as $key=>$value) {
            if (strtolower($key) == strtolower($name)) {
                return explode(',', $value);
            }
        }
        return [];
    }

    /**
     * @param $name
     * @return string
     */
    public function getHeaderLine($name) : string
    {
        foreach ($this->envelope as $key=>$value) {
            if (strtolower($key) == strtolower($name)) {
                return $value;
            }
        }
        return '';
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function withHeader($name, $value) : TraceableMessage
    {
        $headers = $this->envelope;
        $headers[$name] = is_array($value) ? implode(',', $value) : $value;
        return $this->replicate(['envelope' => $headers]);
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function withAddedHeader($name, $value) : TraceableMessage
    {
        if (!isset($this->envelope[$name])) {
            return $this->withHeader($name, $value);
        }
        $value = $this->envelope[$name] . ',' . (is_array($value) ? implode(',', $value) : $value);
        return $this->withHeader($name, $value);
    }

    /**
     * @param $name
     * @return $this
     */
    public function withoutHeader($name) : TraceableMessage
    {
        $headers = $this->envelope;
        if (isset($headers[$name])) {
            unset($headers[$name]);
        }
        return $this->replicate(['envelope' => $headers]);
    }

    /**
     * @return StreamInterface|AbstractStream
     */
    public function getBody() : StreamInterface
    {
        if ($this->payload instanceof StreamInterface) {
            return $this->payload;
        }
        if (is_scalar($this->payload)) {
            return new StringStream((string)$this->payload);
        }
        if ($this->payload instanceof Stringable) {
            return new StringStream($this->payload);
        }
        if (method_exists($this->payload,  '__toString')) {
            return new StringStream($this->payload->__toString());
        }
        return new StringStream('');
    }

    /**
     * @param StreamInterface $body
     * @return $this
     */
    public function withBody(StreamInterface $body) : TraceableMessage
    {
        return $this->replicate(['payload' => $body]);
    }

    public function __get(string $key)
    {
        switch ($key) {
            case 'previous':
                return $this->previous;
            case 'next':
                return $this->next;
            case 'headers':
                return $this->envelope;
            case 'body':
                return $this->getBody();
            case 'protocolVersion':
                return $this->protocolVersion;
        }
        return parent::__get($key);
    }

    protected function apply(array $attributes)
    {
        if (isset($attributes[static::POOL_CUSTOM])) {
            $this->custom = $attributes[static::POOL_CUSTOM];
        }
        if (isset($attributes['envelope'])) {
            $this->envelope = $attributes['envelope'];
        }
        if (isset($attributes['payload'])) {
            $this->payload = $attributes['payload'];
        }
        if (isset($attributes['protocolVersion'])) {
            $this->protocolVersion = $attributes['protocolVersion'];
        }

        if (isset($attributes['previous'])) {
            $this->previous = $attributes['previous'];
        }

    }

    /**
     * @param array $attributes
     * @return $this
     */
    protected function replicate(array $attributes=[]) : TraceableMessage
    {
        $this->copyInto($attributes);
        $this->next = new static($attributes);
        return $this->next;
    }

    protected function copyInto(array &$attributes)
    {
        if (!isset($attributes[static::POOL_CUSTOM])) {
            $attributes[static::POOL_CUSTOM] = $this->custom;
        }
        if (!isset($attributes['envelope'])) {
            $attributes['envelope'] = $this->envelope;
        }
        if (!isset($attributes['payload'])) {
            $attributes['payload'] = $this->payload;
        }
        if (!isset($attributes['protocolVersion'])) {
            $attributes['protocolVersion'] = $this->protocolVersion;
        }
        $attributes['previous'] = $this;
    }
}

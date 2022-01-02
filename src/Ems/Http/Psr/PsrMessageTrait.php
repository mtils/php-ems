<?php
/**
 *  * Created by mtils on 18.12.2021 at 09:45.
 **/

namespace Ems\Http\Psr;

use Ems\Contracts\Core\Stringable;
use Ems\Core\ImmutableMessage;
use Ems\Core\Filesystem\AbstractStream;
use Ems\Core\Filesystem\StringStream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

use function explode;
use function implode;
use function is_array;
use function is_scalar;
use function method_exists;
use function strtolower;

/**
 * This trait fulfills psr-7 message interface in AbstractMessage
 * @see MessageInterface
 */
trait PsrMessageTrait
{
    /**
     * @var string
     */
    protected $protocolVersion = '';

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
    public function withProtocolVersion($version) : ImmutableMessage
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
    public function withHeader($name, $value) : ImmutableMessage
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
    public function withAddedHeader($name, $value) : ImmutableMessage
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
    public function withoutHeader($name) : ImmutableMessage
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
    public function withBody(StreamInterface $body) : ImmutableMessage
    {
        return $this->replicate(['payload' => $body]);
    }
}
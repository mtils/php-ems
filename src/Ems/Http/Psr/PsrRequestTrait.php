<?php
/**
 *  * Created by mtils on 19.12.2021 at 06:11.
 **/

namespace Ems\Http\Psr;

use Ems\Contracts\Routing\Input;
use Ems\Core\Url;
use Psr\Http\Message\UriInterface;

trait PsrRequestTrait
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

    /**
     *
     * @return string
     */
    public function getRequestTarget()
    {
        return $this->requestTarget;
    }

    public function withRequestTarget($requestTarget)
    {
        return $this->replicate(['requestTarget' => $requestTarget]);
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        return $this->replicate(['method' => $method]);
    }

    public function getUri()
    {
        if ($this instanceof Input) {
            return $this->getUrl();
        }
        if ($this->uri) {
            return $this->uri;
        }
        return new Url();
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return $this->replicate(['uri' => $uri, 'preserveHost' => $preserveHost]);
    }
}
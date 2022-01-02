<?php
/**
 *  * Created by mtils on 19.12.2021 at 07:46.
 **/

namespace Ems\Routing;

use Ems\Contracts\Core\Message;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\GenericRouteScope;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteScope;

class GenericInput extends Message implements Input
{
    use InputTrait;

    /**
     * @var string
     */
    protected $method = '';

    public function setDeterminedContentType(string $contentType) : GenericInput
    {
        $this->determinedContentType = $contentType;
        return $this;
    }

    public function offsetSet($offset, $value)
    {
        $this->custom[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->custom[$offset]);
    }

    public function setMethod(string $method): GenericInput
    {
        $this->method = $method;
        return $this;
    }

    public function setUrl(Url $url): GenericInput
    {
        $this->url = $url;
        return $this;
    }

    public function setRouteScope($scope): GenericInput
    {
        $this->applyRouteScope($scope);
        return $this;
    }

    /**
     * @param string $clientType
     * @return $this
     */
    public function setClientType(string $clientType): Input
    {
        $this->clientType = $clientType;
        return $this;
    }

    public function makeRouted(Route $route, callable $handler, array $parameters = []): Input
    {
        $this->matchedRoute = $route;
        $this->handler = $handler;
        $this->routeParameters = $parameters;
        return $this;
    }


}
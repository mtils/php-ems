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

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        if (!$this->clientType) {
            $this->clientType = Input::CLIENT_WEB;
        }
    }


    public function setDeterminedContentType(string $contentType) : GenericInput
    {
        $this->determinedContentType = $contentType;
        return $this;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->custom[$offset] = $value;
    }

    #[\ReturnTypeWillChange]
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

    /**
     * Shortcut to create an input object with the passed client type.
     *
     * @param string                 $clientType
     * @param string|RouteScope|null $scope
     *
     * @return Input|GenericInput
     */
    public static function clientType(string $clientType, $scope=null)
    {
        $input = (new static())->setClientType($clientType);
        return $scope === null ? $input : $input->setRouteScope($scope);
    }

    /**
     * Shortcut to create an input object just for the passed scope.
     *
     * @param string|RouteScope     $scope
     * @param string|null           $clientType
     *
     * @return Input|GenericInput
     */
    public static function scope($scope, string $clientType=null)
    {
        $input = (new static())->setRouteScope($scope);
        return $clientType === null ? $input : $input->setClientType($clientType);
    }
}
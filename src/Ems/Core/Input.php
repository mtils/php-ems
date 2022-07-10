<?php
/**
 *  * Created by mtils on 22.08.18 at 13:31.
 **/

namespace Ems\Core;

use ArrayAccess;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\GenericRouteScope;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteScope;

class Input extends ImmutableMessage
{

    /**
     * @var RouteScope
     */
    protected $routeScope;

    /**
     * @var Url
     */
    protected $url;

    /**
     * @var string
     */
    protected $clientType = '';

    /**
     * @var Route
     */
    protected $matchedRoute;

    /**
     * @var array
     */
    protected $routeParameters = [];

    /**
     * @var callable
     */
    protected $handler;

    /**
     * @var string
     */
    protected $determinedContentType = 'text/x-ansi';

    /**
     * Input constructor.
     *
     * @param array $parameters
     */
    public function __construct($parameters=[])
    {
        $this->custom = $parameters;
    }

    /**
     * @return RouteScope
     **/
    public function routeScope()
    {
        return $this->routeScope;
    }

    /**
     * @param string|RouteScope $scope
     *
     * @return $this
     */
    public function setRouteScope($scope)
    {
        $this->routeScope = $scope instanceof RouteScope ? $scope : new GenericRouteScope($scope, $scope);
        return $this;
    }

    /**
     * @return Url
     **/
    public function url()
    {
        if (!$this->url) {
            $this->url = new \Ems\Core\Url();
        }
        return $this->url;
    }

    /**
     * @param Url $url
     *
     * @return $this
     */
    public function setUrl(Url $url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function clientType()
    {
        return $this->clientType;
    }

    /**
     * @param string $clientType
     *
     * @return $this
     */
    public function setClientType($clientType)
    {
        $this->clientType = $clientType;
        return $this;
    }

    /**
     * @return Route
     */
    public function matchedRoute()
    {
        return $this->matchedRoute;
    }

    /**
     * @param Route $route
     *
     * @return $this
     */
    public function setMatchedRoute(Route $route)
    {
        $this->matchedRoute = $route;
        return $this;
    }

    /**
     * @return ArrayAccess|array
     */
    public function routeParameters()
    {
        return $this->routeParameters;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function setRouteParameters(array $parameters)
    {
        $this->routeParameters = $parameters;
        return $this;
    }

    /**
     * Return the actual handler
     *
     * @return callable|null
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Assign the actual handler.
     *
     * @param callable $handler
     *
     * @return $this
     */
    public function setHandler(callable $handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Returns true if this object is routed.
     *
     * @return bool
     */
    public function isRouted()
    {
        return $this->matchedRoute && $this->handler;
    }

    /**
     * @return string
     */
    public function determinedContentType(): string
    {
        return $this->determinedContentType;
    }

    /**
     * @param string $contentType
     * @return Input
     */
    public function setDeterminedContentType(string $contentType) : Input
    {
        $this->determinedContentType = $contentType;
        return $this;
    }
}
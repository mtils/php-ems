<?php
/**
 *  * Created by mtils on 22.08.18 at 13:55.
 **/

namespace Ems\Core\Support;


use ArrayAccess;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\GenericRouteScope;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteScope;

/**
 * Trait RoutableTrait
 * @package Ems\Core\Support
 * @see Routable
 */
trait RoutableTrait
{
    /**
     * @var RouteScope
     */
    protected $_routeScope;

    /**
     * @var Url
     */
    protected $_url;

    /**
     * @var string
     */
    protected $_method = '';

    /**
     * @var string
     */
    protected $_clientType = '';

    /**
     * @var Route
     */
    protected $_matchedRoute;

    /**
     * @var array
     */
    protected $_routeParameters = [];

    /**
     * @var callable
     */
    protected $_handler;

    /**
     * @return RouteScope
     **/
    public function routeScope()
    {
        return $this->_routeScope;
    }

    /**
     * @param string|RouteScope $scope
     *
     * @return $this
     */
    public function setRouteScope($scope)
    {
        $this->_routeScope = $scope instanceof RouteScope ? $scope : new GenericRouteScope($scope, $scope);
        return $this;
    }

    /**
     * @return Url
     **/
    public function url()
    {
        if (!$this->_url) {
            $this->_url = new \Ems\Core\Url();
        }
        return $this->_url;
    }

    /**
     * @param Url $url
     *
     * @return $this
     */
    public function setUrl(Url $url)
    {
        $this->_url = $url;
        return $this;
    }

    /**
     * Return the access method (get, post, console, scheduled,...)
     *
     * @return string
     **/
    public function method()
    {
        return $this->_method;
    }

    /**
     * @param string $method
     *
     * @return self
     */
    public function setMethod($method)
    {
        $this->_method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function clientType()
    {
        return $this->_clientType;
    }

    /**
     * @param string $clientType
     *
     * @return $this
     */
    public function setClientType($clientType)
    {
        $this->_clientType = $clientType;
        return $this;
    }

    /**
     * @return Route
     */
    public function matchedRoute()
    {
        return $this->_matchedRoute;
    }

    /**
     * @param Route $route
     *
     * @return $this
     */
    public function setMatchedRoute(Route $route)
    {
        $this->_matchedRoute = $route;
        return $this;
    }

    /**
     * @return ArrayAccess|array
     */
    public function routeParameters()
    {
        return $this->_routeParameters;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function setRouteParameters(array $parameters)
    {
        $this->_routeParameters = $parameters;
        return $this;
    }

    /**
     * Return the actual handler
     *
     * @return callable|null
     */
    public function getHandler()
    {
        return $this->_handler;
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
        $this->_handler = $handler;
        return $this;
    }

    /**
     * Returns true if this object is routed.
     *
     * @return bool
     */
    public function isRouted()
    {
        return $this->_matchedRoute && $this->_handler;
    }
}
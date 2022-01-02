<?php
/**
 *  * Created by mtils on 19.12.2021 at 07:48.
 **/

namespace Ems\Routing;

use ArrayAccess;
use Ems\Contracts\Core\None;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\GenericRouteScope;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteScope;

trait InputTrait
{
    /**
     * @var Url
     */
    protected $url;

    /**
     * @var RouteScope
     */
    protected $routeScope;

    /**
     * @var string
     */
    protected $clientType = '';

    /**
     * @var Route
     */
    protected $matchedRoute;

    /**
     * @var array|ArrayAccess
     */
    protected $routeParameters = [];

    /**
     * @var callable
     */
    protected $handler;

    /**
     * @var string
     */
    protected $locale = '';

    /**
     * @var string
     */
    protected $determinedContentType = '';

    /**
     * @var string
     */
    protected $apiVersion = '';

    /**
     * @return Url
     **/
    public function getUrl() : Url
    {
        if (!$this->url) {
            $this->url = new \Ems\Core\Url();
        }
        return $this->url;
    }

    /**
     * Return the access method (get, post, console, scheduled,...)
     *
     * @return string
     **/
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getClientType() : string
    {
        return $this->clientType;
    }

    /**
     * @return RouteScope|null
     **/
    public function getRouteScope()
    {
        return $this->routeScope;
    }

    /**
     * @return Route|null
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }

    /**
     * @param Route $route
     * @param callable $handler
     * @param array $parameters
     * @return Input
     */
    public function makeRouted(Route $route, callable $handler, array $parameters=[]) : Input
    {
        return $this->replicate([
            'matchedRoute'      => $route,
            'handler'           => $handler,
            'routeParameters'   => $parameters
        ]);
    }

    /**
     * @return ArrayAccess|array
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
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
     * Returns true if this object is routed.
     *
     * @return bool
     */
    public function isRouted() : bool
    {
        return $this->matchedRoute && $this->handler;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getDeterminedContentType() : string
    {
        return $this->determinedContentType;
    }

    /**
     * @return string
     */
    public function getApiVersion() : string
    {
        return $this->apiVersion;
    }

    /**
     * @param RouteScope|string|Stringable $scope
     * @return Input
     */
    public function withRouteScope($scope) : Input
    {
        return $this->replicate(['routeScope' => $scope]);
    }

    /**
     * @param string $locale
     * @return Input
     */
    public function withLocale(string $locale) : Input
    {
        return $this->replicate(['locale' => $locale]);
    }

    /**
     * @param string $contentType
     * @return Input
     */
    public function withDeterminedContentType(string $contentType) : Input
    {
        return $this->replicate(['determinedContentType' => $contentType]);
    }

    protected function applyInputTrait(array $attributes)
    {
        if (isset($attributes['url'])) {
            $this->url = $attributes['url'];
        }
        if (isset($attributes['method'])) {
            $this->method = $attributes['method'];
        }
        if (isset($attributes['clientType'])) {
            $this->clientType = $attributes['clientType'];
        }
        if (isset($attributes['routeScope'])) {
            $this->applyRouteScope($attributes['routeScope']);
        }
        if (isset($attributes['matchedRoute'])) {
            $this->matchedRoute = $attributes['matchedRoute'];
        }
        if (isset($attributes['handler'])) {
            $this->handler = $attributes['handler'];
        }
        if (isset($attributes['routeParameters'])) {
            $this->routeParameters = $attributes['routeParameters'];
        }
        if (isset($attributes['locale'])) {
            $this->locale = $attributes['locale'];
        }
        if (isset($attributes['determinedContentType'])) {
            $this->determinedContentType = $attributes['determinedContentType'];
        }
        if (isset($attributes['apiVersion'])) {
            $this->apiVersion = $attributes['apiVersion'];
        }
    }

    protected function copyInputTraitStateInto(array &$attributes)
    {
        if (!isset($attributes['url'])) {
            $attributes['url'] = $this->url;
        }
        if (!isset($attributes['method'])) {
            $attributes['method'] = $this->method;
        }
        if (!isset($attributes['clientType'])) {
            $attributes['clientType'] = $this->clientType;
        }
        if (!isset($attributes['routeScope'])) {
            $attributes['routeScope'] = $this->routeScope;
        }
        if (!isset($attributes['matchedRoute'])) {
            $attributes['matchedRoute'] = $this->matchedRoute;
        }
        if (!isset($attributes['handler'])) {
            $attributes['handler'] = $this->handler;
        }
        if (!isset($attributes['routeParameters'])) {
            $attributes['routeParameters'] = $this->routeParameters;
        }
        if (!isset($attributes['locale'])) {
            $attributes['locale'] = $this->locale;
        }
        if (!isset($attributes['determinedContentType'])) {
            $attributes['determinedContentType'] = $this->determinedContentType;
        }
        if (!isset($attributes['apiVersion'])) {
            $attributes['apiVersion'] = $this->apiVersion;
        }
    }

    /**
     * @param string $key
     * @return array|ArrayAccess|callable|None|Url|Route|RouteScope|string|null
     */
    protected function getInputTraitProperty(string $key)
    {
        switch ($key) {
            case 'url':
                return $this->getUrl();
            case 'routeScope':
                return $this->getRouteScope();
            case 'method':
                return $this->getMethod();
            case 'clientType':
                return $this->getClientType();
            case 'matchedRoute':
                return $this->getMatchedRoute();
            case 'routeParameters':
                return $this->getRouteParameters();
            case 'handler':
                return $this->getHandler();
            case 'locale':
                return $this->getLocale();
            case 'determinedContentType':
                return $this->getDeterminedContentType();
            case 'apiVersion':
                return $this->getApiVersion();
        }
        return new None();
    }

    /**
     * @param string|RouteScope|Stringable $scope
     * @return void
     */
    protected function applyRouteScope($scope)
    {
        $this->routeScope = $scope instanceof RouteScope ? $scope : new GenericRouteScope($scope, $scope);
    }
}
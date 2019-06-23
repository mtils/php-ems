<?php
/**
 *  * Created by mtils on 16.08.18 at 15:14.
 **/

namespace Ems\Contracts\Routing;


use function array_values;
use Ems\Contracts\Core\Arrayable;
use function func_get_args;

/**
 * Class Route
 *
 * Objects of this class hold information about one route.
 * A Route is just a value object and does not contain a lot of processing.
 * All processing has to be done by the dispatcher or middlewares.
 * With this objects you are able to assign routes by a fluent interface:
 *
 * Registrar::get('users', function () {})->name('users.index')
 *
 * Like in Ems\Contracts\Url: properties are for read access, methods for write
 * access.
 *
 * @package Ems\Contracts\Routing
 *
 * @property-read array     $methods     (http)-methods The assigned methods of this route
 * @property-read string    $uri         The route uri
 * @property-read mixed     $handler     The assigned (whatever) handler
 * @property-read string    $name        The unique name of this route
 * @property-read array     $middlewares The middlewares of this route
 * @property-read string[]  $clientTypes The types of client which have access to this route
 * @property-read string[]  $scopes      The RouteScopes in which this route applies
 * @property-read array     $defaults    The default route parameters
 */
class Route implements Arrayable
{
    /**
     * @var array
     */
    protected $properties = [
        'methods'     => [],
        'uri'         => '',
        'handler'     => null,
        'name'        => '',
        'middlewares' => [],
        'clientTypes' => [],
        'scopes'      => [],
        'defaults'    => []
    ];

    /**
     * RouteConfiguration constructor.
     *
     * @param string|array $method (optional)
     * @param string       $uri (optional)
     * @param mixed        $handler (optional)
     */
    public function __construct($method=[], $uri='', $handler=null)
    {
        $this->method($method);
        $this->uri($uri);
        if ($handler) {
            $this->handler($handler);
        }
    }

    /**
     * Assign the http method(s) which should handled by this route
     *
     * @param $method
     *
     * @return $this
     */
    public function method($method)
    {
        $this->properties['methods'] = is_array($method) ? $method : func_get_args();
        return $this;
    }

    /**
     * Assign the url of this route.
     *
     * @param string $uri
     * @return $this
     */
    public function uri($uri)
    {
        $this->properties['uri'] = $uri;
        return $this;
    }

    /**
     * Assign a handler for this route
     *
     * @param mixed $handler
     *
     * @return $this
     */
    public function handler($handler)
    {
        $this->properties['handler'] = $handler;
        return $this;
    }

    /**
     * Give this route name to reference it (by an url generator)
     * @param string $name
     *
     * @return $this
     */
    public function name($name)
    {
        $this->properties['name'] = $name;
        return $this;
    }

    /**
     * Determine that $middleware should be executed before running this route.
     * A string assigns one middleware, many strings or an array assigns multiple
     * middlewares.
     * If you just call middleware() without a parameter it resets the middleware
     * to no middleware for this route.
     * You can alco call it multiple times for multiple middleware.
     *
     * @param string|array|null $middleware (optional)
     *
     * @return $this
     */
    public function middleware($middleware=null)
    {
        if ($middleware === null) {
            $this->properties['middlewares'] = [];
            return $this;
        }

        $middlewares = is_array($middleware) ? array_values($middleware) : func_get_args();

        foreach ($middlewares as $singleMiddleware) {
            $this->properties['middlewares'][] = $singleMiddleware;
        }
        return $this;
    }

    /**
     * Let this route match only to distinct client types.
     * Client types are: api|console|web|cms|desktop|mobile|whatever
     *
     * @param string|array $type
     *
     * @return $this
     */
    public function clientType($type)
    {
        $this->properties['clientTypes'] = is_array($type) ? $type : func_get_args();
        return $this;
    }

    /**
     * Restrict this route to just be callable in scope.
     *
     * @param string|RouteScope|array $scope
     *
     * @return $this
     */
    public function scope($scope)
    {
        $this->properties['scopes'] = is_array($scope) ? $scope : func_get_args();
        return $this;
    }

    /**
     * Set some parameter defaults.
     *
     * @param string|array $key
     * @param mixed        $value (optional)
     *
     * @return $this
     */
    public function defaults($key, $value=null)
    {
        if (is_array($key)) {
            $this->properties['defaults'] = $key;
            return $this;
        }
        $this->properties['defaults'][$key] = $value;
        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->properties[$name];
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     **/
    public function toArray()
    {
        return $this->properties;
    }


}

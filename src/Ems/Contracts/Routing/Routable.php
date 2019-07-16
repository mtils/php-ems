<?php
/**
 *  * Created by mtils on 19.08.18 at 13:17.
 **/

namespace Ems\Contracts\Routing;


use ArrayAccess;
use Ems\Contracts\Core\Url;

interface Routable
{
    const GET = 'GET';

    const POST = 'POST';

    const PUT = 'PUT';

    const DELETE = 'DELETE';

    const HEAD = 'HEAD';

    const OPTIONS = 'OPTIONS';

    const PATCH = 'PATCH';

    const CONSOLE = 'CONSOLE';

    // cron or queue
    const SCHEDULED = 'SCHEDULED';

    /**
     * The web client type (browser in general, can also be mobile)
     */
    const CLIENT_WEB = 'web';

    /**
     * An API client is accessing the app
     */
    const CLIENT_API = 'api';

    /**
     * A console client (e.g. artisan call)
     */
    const CLIENT_CONSOLE = 'console';

    /**
     * The application is used or decorated by a cms
     */
    const CLIENT_CMS = 'cms';

    /**
     * A desktop application
     */
    const CLIENT_DESKTOP = 'desktop';

    /**
     * A mobile application (something like jQuery Mobile)
     */
    const CLIENT_MOBILE = 'mobile';

    /**
     * An ajax client (javascript using the page)
     */
    const CLIENT_AJAX = 'ajax';

    /**
     * Something like cron, a scheduler or AWS task...
     */
    const CLIENT_TASK = 'task';

    /**
     * This is just a shortcut to note that you want to add a route for all clienTypes/scopes
     */
    const ALL = '*';

    /**
     * Return the route scope. This is an artificial subset of routes to explicitly
     * allow routes in one scope vs another.
     *
     * @see RouteScope
     *
     * @return RouteScope|null
     **/
    public function routeScope();

    /**
     * @param string|RouteScope $scope
     *
     * @return $this
     */
    public function setRouteScope($scope);

    /**
     * Return the completely routed url. (Not only the path)
     *
     * @return Url
     **/
    public function url();

    /**
     * @param Url $url
     *
     * @return $this
     */
    public function setUrl(Url $url);

    /**
     * Return the access method (get, post, console, scheduled,...)
     *
     * @return string
     **/
    public function method();

    /**
     * @param string $method
     *
     * @return self
     */
    public function setMethod($method);

    /**
     * Return the type of client. web|api|console...
     *
     * @return string
     *
     * @see static::CLIENT_WEB...
     */
    public function clientType();

    /**
     * @param string $clientType
     *
     * @return $this
     */
    public function setClientType($clientType);

    /**
     * If this method returns something you can assume the routable is routed.
     *
     * @return Route|null
     */
    public function matchedRoute();

    /**
     * @param Route $route
     *
     * @return $this
     */
    public function setMatchedRoute(Route $route);

    /**
     * Return the parameters that were assigned by the route pattern.
     * (/users/{user_id/ + /users/112 = ['user_id' => 112])
     *
     * @return ArrayAccess|array
     */
    public function routeParameters();

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function setRouteParameters(array $parameters);
}
<?php
/**
 *  * Created by mtils on 19.12.2021 at 07:23.
 **/

namespace Ems\Contracts\Routing;

use ArrayAccess;
use Countable;
use Ems\Contracts\Core\Url;
use IteratorAggregate;

/**
 * This interface represents what an application gets as input.
 * In psr-7 requests are immutable. The http part of ems is built compatible to
 * this.
 */
interface Input extends ArrayAccess, IteratorAggregate, Countable
{
    /************************************************************
     * Access methods (HTTP verbs)
     ***********************************************************/
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

    /************************************************************
     * Parameter sources
     ***********************************************************/

    /**
     * Parameters derived from url
     */
    const FROM_QUERY = 'query';

    /**
     * Parameters derived from parsed request body (form encoded/json/...)
     */
    const FROM_BODY = 'body';

    /**
     * Parameters from cookie headers
     */
    const FROM_COOKIE = 'cookie';

    /**
     * Parameters from $_SERVER array
     */
    const FROM_SERVER = 'server';

    /**
     * Parameters from $_FILES
     */
    const FROM_FILES = 'files';

    /************************************************************
     * Client types
     ***********************************************************/

    /**
     * The web client type (browser in general, can also be mobile)
     */
    const CLIENT_WEB = 'web';

    /**
     * An API client is accessing the app
     */
    const CLIENT_API = 'api';

    /**
     * A console client (e.g. artisan call or console)
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
     * A mobile application (something like jQuery Mobile, not a responsive app)
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
     * This is just a shortcut to note that you want to add a route for all clientTypes/scopes
     */
    const ALL = '*';

    /**
     * Return the completely routed url. (Not only the path)
     *
     * @return Url
     **/
    public function getUrl() : Url;

    /**
     * Return the access method (get, post, console, scheduled,...)
     *
     * @return string
     **/
    public function getMethod() : string;

    /**
     * Return the type of client. web|api|console...
     *
     * @return string
     *
     * @see static::CLIENT_WEB...
     */
    public function getClientType() : string;

    /**
     * Return the route scope. This is an artificial subset of routes to explicitly
     * allow routes in one scope vs another.
     *
     * @return RouteScope|null
     */
    public function getRouteScope();

    /**
     * Route the input. Assign the matched route, the handler and the parsed
     * route parameters.
     * The result of this method is used to pass it to further middlewares:
     * $input = $input->makeRouted($route, f(), [])
     * ... so feel free to make your input immutable like psr-7 or return the
     * same instance.
     *
     * @param Route     $route
     * @param callable  $handler
     * @param array     $parameters (optional)
     *
     * @return Input
     */
    public function makeRouted(Route $route, callable $handler, array $parameters=[]) : Input;

    /**
     * If this method returns something you can assume the routable is routed.
     *
     * @return Route|null
     */
    public function getMatchedRoute();

    /**
     * Return the parameters that were assigned by the route pattern.
     * (/users/{user_id}/ + /users/112 = ['user_id' => 112])
     *
     * @return ArrayAccess|array
     */
    public function getRouteParameters();

    /**
     * Return the actual handler for the route. If you call this handler the
     * request will be considered as "handled".
     * In contrast to matchedRoute()->handler this one here is callable.
     *
     * @return callable|null
     */
    public function getHandler();

    /**
     * Return true if this object was routed. (Handler and route was assigned)
     *
     * @return bool
     */
    public function isRouted() : bool;

    /**
     * Return the *requested* locale.
     *
     * @return string
     */
    public function getLocale() : string;

    /**
     * Return the content type that should be returned by the application when
     * responding to this input. This should be accepted by the client and match
     * the clientType.
     *
     * @return string
     */
    public function getDeterminedContentType() : string;

    /**
     * Return the *requested* api version (makes only sense if this is an api
     * request)
     *
     * @return string
     */
    public function getApiVersion() : string;

    /**
     * Return the "current" user.
     *
     * @return object
     */
    public function getUser() : object;
}
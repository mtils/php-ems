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
     * @return RouteScope|null
     **/
    public function routeScope();

    /**
     * @return Url
     **/
    public function url();

    /**
     * Return the access method (get, post, console, scheduled,...)
     *
     * @return string
     **/
    public function method();

    /**
     * @return string
     */
    public function clientType();

    /**
     * @return Route|null
     */
    public function matchedRoute();

    /**
     * @return ArrayAccess|array
     */
    public function routeParameters();

}
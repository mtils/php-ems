<?php
/**
 *  * Created by mtils on 19.08.18 at 13:17.
 **/

namespace Ems\Contracts\Routing;


use ArrayAccess;
use Ems\Contracts\Core\Url;

interface Routable
{
    const GET = 'get';

    const POST = 'post';

    const PUT = 'put';

    const DELETE = 'delete';

    const HEAD = 'head';

    const OPTIONS = 'options';

    const CONSOLE = 'console';

    // cron or queue
    const SCHEDULED = 'scheduled';

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
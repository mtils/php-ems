<?php
/**
 *  * Created by mtils on 17.08.18 at 14:52.
 **/

namespace Ems\Contracts\Routing;

use Ems\Contracts\Core\Named;
use Ems\Contracts\Core\Stringable;

/**
 * A RouteScope is one scope for routes. It could be a domain, subdomain or
 * the first subfolder. It is used to separate sets of routes from each other.
 * So in scope A you have this paths (routes) and in scope B you have another
 * set. In most cases you will have the same application routes in every scope
 * but different cms/page paths in every scope (/en or /us or europe.mydomain.com)
 *
 * @package Ems\Contracts\Routing
 */
interface RouteScope extends Named, Stringable
{
    /**
     * Return alternative names to match this scope.
     *
     * @return string[]
     */
    public function aliases();
}
<?php
/**
 *  * Created by mtils on 19.08.18 at 11:28.
 **/

namespace Ems\Contracts\Routing;


use Ems\Contracts\Core\Arrayable;

/**
 * Interface Dispatcher
 *
 * The Dispatcher is the most important part of the routing process. You add
 * the route definitions by ->add() and the Interpreter collects that in its own
 * (array) format.
 * Then in match() it returns the whatever handler you put into it.
 * The toArray() returns some array of all added routes. This must be in a format
 * that allows to fill it with that routes in fill().
 * This is needed to cache routes and omit the repeating compile/add tasks.
 *
 *
 * @package Ems\Contracts\Routing
 */
interface Dispatcher extends Arrayable
{
    /**
     * Add a route (definition). Whatever you put into it as $handler you will
     * get returned in match.
     *
     * @param string $method
     * @param string $pattern
     * @param mixed  $handler
     */
    public function add($method, $pattern, $handler);

    /**
     * Find the handler for $method and $uri that someone did add().
     *
     * @param string   $method
     * @param string   $uri
     *
     * @return RouteHit
     */
    public function match($method, $uri);

    /**
     * Fill the interpreter with route definitions that he did export by toArray()
     *
     * @param array $data
     *
     * @return bool
     */
    public function fill(array $data);

    /**
     * Render a path by the route pattern and parameters.
     *
     * @param string $pattern
     * @param array $parameters (optional)
     *
     * @return string
     */
    public function path($pattern, array $parameters=[]);
}
<?php
/**
 *  * Created by mtils on 30.06.19 at 10:54.
 **/

namespace Ems\Contracts\Routing;


use IteratorAggregate;

interface Router extends IteratorAggregate
{
    /**
     * Pass a callable that will register routes. The callable will be called
     * with a RouteCollector instance that allows to add your routes.
     *
     * @example $router->register(function (RouteCollector $routes) {
     *     $routes->get('foo', 'BarController->fooAction');
     * });
     *
     * Why not directly assign routes? This is a performance decision. If you
     * cache your routes the Router can skip every call inside your callable.
     * So be aware to only register inside this callable. The whole code will be
     * skipped on cached requests.
     *
     * @param callable $registrar
     */
    public function register(callable $registrar);

    /**
     * Make the routable routed. At the end find a route and a handler, assign it
     * to the passed routable. After
     *
     * @param Routable $routable
     */
    public function route(Routable $routable);

    /**
     * Get all routes that have $pattern. Optionally pass a (http) $method to
     * further narrow down the result.
     *
     * @param string $pattern
     * @param string $method (optional)
     *
     * @return Route[]
     */
    public function getByPattern($pattern, $method=null);

    /**
     * Get a route by its name.
     *
     * @param string $name
     *
     * @return Route
     */
    public function getByName($name);

}
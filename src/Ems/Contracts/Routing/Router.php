<?php
/**
 *  * Created by mtils on 30.06.19 at 10:54.
 **/

namespace Ems\Contracts\Routing;


use IteratorAggregate;

interface Router extends IteratorAggregate
{
    /**
     * The key for passing common middleware attributes in register().
     * Passing middleware per route should add them to common middleware.
     */
    const MIDDLEWARE = 'middleware';

    /**
     * The key for passing common client type attributes in register().
     * Passing client types per route should replace the common ones.
     */
    const CLIENT = 'client';

    /**
     * The key for passing common route scope attributes in register().
     * Passing scopes per route should replace the common ones.
     */
    const SCOPE = 'scope';

    /**
     * The key for passing a common path prefix attributes in register().
     * The common prefix will be (fully) added before the path for each route.
     */
    const PREFIX = 'prefix';

    /**
     * The key for passing a common handler class prefix attributes in register().
     * The common prefix will be added before the handler string of each route.
     * So you could add UserController::class as a controller and then only
     * write edit, create,... in your routes.
     */
    const CONTROLLER = 'controller';

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
     * The second parameter is for passing common attributes for all routes
     * you register in your callable.
     *
     * @param callable $registrar
     * @param array    $attributes (optional)
     */
    public function register(callable $registrar, array $attributes=[]);

    /**
     * Make the routable routed. At the end find a route and a handler, assign it
     * to the passed routable. It returns the result of Input::makeRouted() so
     * depending on the input you will get a new instance. (immutable input)
     *
     * @param Input $routable
     *
     * @return Input
     */
    public function route(Input $routable) : Input;

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

    /**
     * Return all known unique client types (by route registrations)
     *
     * @return string[]
     */
    public function clientTypes();

    /**
     * Return the dispatcher for $clientType. This is needed for the UrlGenerator
     * to compile the urls by the dispatcher (who should now his urls) and compilation
     * of routes by a cached proxy.
     *
     * @param string $clientType
     *
     * @return Dispatcher
     */
    public function getDispatcher($clientType);

}
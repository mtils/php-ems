<?php
/**
 *  * Created by mtils on 15.06.2022 at 06:58.
 **/

namespace Ems\Contracts\Routing;

use Countable;
use IteratorAggregate;

/**
 * The route registry is the central place to register routes. It is used by the
 * router and url generator as a repository. This is the place to implement
 * caching, compilation and optimizing routes.
 */
interface RouteRegistry extends IteratorAggregate, Countable
{
    /**
     * Pass a callable that will register routes. The callable will be called
     * with a RouteCollector instance that allows to add your routes.
     *
     * @example $router->register(function (RouteCollector $routes) {
     *     $routes->get('foo', [BarController::class, 'fooAction']);
     * });
     *
     * You can also decline the RouteCollector and just return an iterable of
     * routes:
     *
     * @example $router->register(function () {
     *     return [
     *         Route::get('foo', [BarController::class, 'fooAction'])
     *     ];
     * });
     *
     * Why not directly assign routes? This is a performance decision. If you
     * cache your routes the RouteRegistry can skip every call inside you're callable.
     * So be aware to only register inside this callable. The whole code will be
     * skipped on cached requests.
     * The passed callable will be normally not called directly.
     * The router should call all registrars when the routes are needed, not
     * before.
     *
     * The second parameter is for passing common attributes for all routes
     * you register in your callable.
     *
     * @param callable $registrar
     * @param array    $attributes (optional)
     */
    public function register(callable $registrar, array $attributes=[]);

    /**
     * Fill the passed dispatcher (either by addRoute or by addRoute())
     *
     * @param Dispatcher $dispatcher
     * @param string     $clientType
     *
     * @return void
     */
    public function fillDispatcher(Dispatcher $dispatcher, string $clientType);

    /**
     * Get all routes that have $pattern. Optionally pass a (http) $method to
     * further narrow down the result.
     *
     * @param string        $pattern
     * @param string|null   $method
     * @param string        $clientType
     *
     * @return Route[]
     */
    public function getByPattern(string $pattern, string $method=null, string $clientType=Input::CLIENT_WEB) : array;

    /**
     * Get a route by its name.
     *
     * @param string $name
     * @param string $clientType
     *
     * @return Route
     */
    public function getByName(string $name, string $clientType=Input::CLIENT_WEB) : Route;

    /**
     * Get a route by an entity action. Pass a or interface class name for absolute
     * equal comparison. Pass an object to get the route of classes or
     * interfaces this objects implements or extends.
     *
     * @param object|string $entity
     * @param string $action
     * @param string $clientType
     *
     * @return Route
     */
    public function getByEntityAction($entity, string $action='index', string $clientType=Input::CLIENT_WEB) : Route;

    /**
     * Return all known unique client types (by route registrations)
     *
     * @return string[]
     */
    public function clientTypes() : array;
}
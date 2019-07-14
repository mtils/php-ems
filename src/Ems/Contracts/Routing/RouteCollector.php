<?php
/**
 *  * Created by mtils on 30.06.19 at 11:00.
 **/

namespace Ems\Contracts\Routing;


use ArrayIterator;
use IteratorAggregate;
use Traversable;

class RouteCollector implements IteratorAggregate
{
    /**
     * @var Route[]
     */
    protected $routes;

    /**
     * Register an handler for a pattern called by $method(s).
     *
     * @param string|string[] $method
     * @param string $pattern
     * @param mixed $handler
     *
     * @return Route
     */
    public function on($method, $pattern, $handler)
    {
        $route = $this->newRoute($method, $pattern, $handler);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * Register an handler for a get pattern.
     *
     * @param string $pattern
     * @param mixed $handler
     *
     * @return Route
     */
    public function get($pattern, $handler)
    {
        return $this->on(Routable::GET, $pattern, $handler);
    }

    /**
     * Register an handler for a post pattern.
     *
     * @param string $pattern
     * @param mixed $handler
     *
     * @return Route
     */
    public function post($pattern, $handler)
    {
        return $this->on(Routable::POST, $pattern, $handler);
    }

    /**
     * Register an handler for a put pattern.
     *
     * @param string $pattern
     * @param mixed $handler
     *
     * @return Route
     */
    public function put($pattern, $handler)
    {
        return $this->on(Routable::PUT, $pattern, $handler);
    }

    /**
     * Register an handler for a delete pattern.
     *
     * @param string $pattern
     * @param mixed $handler
     *
     * @return Route
     */
    public function delete($pattern, $handler)
    {
        return $this->on(Routable::DELETE, $pattern, $handler);
    }

    /**
     * Register an handler for a patch pattern.
     *
     * @param string $pattern
     * @param mixed $handler
     *
     * @return Route
     */
    public function patch($pattern, $handler)
    {
        return $this->on(Routable::PATCH, $pattern, $handler);
    }

    /**
     * Register an handler for a options pattern.
     *
     * @param string $pattern
     * @param mixed $handler
     *
     * @return Route
     */
    public function options($pattern, $handler)
    {
        return $this->on(Routable::OPTIONS, $pattern, $handler);
    }

    /**
     * Retrieve an external iterator
     *
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     *
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }

    /**
     * @param string $method
     * @param string $pattern
     * @param mixed  $handler
     *
     * @return Route
     */
    protected function newRoute($method, $pattern, $handler)
    {
        return new Route($method, $pattern, $handler);
    }
}
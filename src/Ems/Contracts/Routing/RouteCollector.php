<?php
/**
 *  * Created by mtils on 30.06.19 at 11:00.
 **/

namespace Ems\Contracts\Routing;


use ArrayIterator;
use IteratorAggregate;
use Traversable;
use function array_map;
use function explode;

class RouteCollector implements IteratorAggregate
{
    /**
     * @var Route[]
     */
    protected $routes = [];

    /**
     * @var Command[]
     */
    protected $commands;

    /**
     * @var array
     */
    protected $common = [];

    /**
     * @var string
     */
    public static $methodSeparator = '->';

    /**
     * @var string
     */
    public static $middlewareDelimiter = ':';

    /**
     * RouteCollector constructor.
     *
     * @param array $common
     */
    public function __construct($common=[])
    {
        $this->common = $common;
    }

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
        $route = $this->newRoute($method, $this->pattern($pattern), $this->handler($handler));
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
        return $this->on(Input::GET, $pattern, $handler);
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
        return $this->on(Input::POST, $pattern, $handler);
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
        return $this->on(Input::PUT, $pattern, $handler);
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
        return $this->on(Input::DELETE, $pattern, $handler);
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
        return $this->on(Input::PATCH, $pattern, $handler);
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
        return $this->on(Input::OPTIONS, $pattern, $handler);
    }

    /**
     * Create a console command.
     *
     * @param string $pattern
     * @param mixed $handler
     * @param string $description (optional)
     *
     * @return Command
     */
    public function command($pattern, $handler, $description='')
    {
        $command = $this->newCommand($pattern, $description);
        $route = $this->on(Input::CONSOLE, $pattern, $handler);
        $route->clientType(Input::CLIENT_CONSOLE);
        $route->name($pattern);
        $route->command($command);
        // The command is also the first argument
        $command->argument('command', 'The command name that should be executed');
        return $command;
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
        if (!$this->common) {
            return new ArrayIterator($this->routes);
        }
        return new ArrayIterator(
            array_map(
                [$this, 'configureRouteByCommonAttributes'],
                $this->routes
            )
        );
    }

    /**
     * Check if routes were added to this collector
     * @return bool
     */
    public function isEmpty() : bool
    {
        return $this->routes === [];
    }

    /**
     * @param string $method
     * @param string $pattern
     * @param mixed  $handler
     *
     * @return Route
     */
    protected function newRoute($method, $pattern, $handler) : Route
    {
        return new Route($method, $pattern, $handler, $this);
    }

    /**
     * @param string  $pattern
     * @param string  $description (optional)
     *
     * @return Command
     */
    protected function newCommand($pattern, $description='')
    {
        return new Command($pattern, $description, $this);
    }

    protected function configureRouteByCommonAttributes(Route $route)
    {
        if (isset($this->common[Router::CLIENT]) && !$route->clientTypes) {
            $route->clientType((array)$this->common[Router::CLIENT]);
        }

        if (isset($this->common[Router::SCOPE]) && !$route->scopes) {
            $route->scope((array)$this->common[Router::SCOPE]);
        }

        if (!isset($this->common[Router::MIDDLEWARE])) {
            return $route;
        }

        if ($route->wasMiddlewareRemoved()) {
            return $route;
        }

        $routeMiddlewares = $route->middlewares;

        $route->middleware(); // clear middleware

        $merged = $this->mergeMiddlewares(
            $routeMiddlewares,
            (array)$this->common[Router::MIDDLEWARE]
        );

        $route->middleware($merged);

        return $route;
    }

    /**
     * @param array $routeMiddleware
     * @param array $commonMiddleware
     *
     * @return array
     */
    protected function mergeMiddlewares(array $routeMiddleware, array $commonMiddleware)
    {
        $routeMiddleware = $this->middlewareByName($routeMiddleware);
        $commonMiddleware = $this->middlewareByName($commonMiddleware);

        $mergedMiddleware = [];

        foreach ($commonMiddleware as $name=>$parameters) {
            if (isset($routeMiddleware[$name])) {
                $mergedMiddleware[] = $this->signature($name, $routeMiddleware[$name]);
                continue;
            }
            $mergedMiddleware[] = $this->signature($name, $parameters);
        }

        // Now add all middlewares that were not in common middleware
        foreach ($routeMiddleware as $name=>$parameters) {
            if (!isset($commonMiddleware[$name])) {
                $mergedMiddleware[] = $this->signature($name, $parameters);
            }
        }

        return $mergedMiddleware;

    }

    /**
     * @param array $middlewares
     * @return array
     */
    protected function middlewareByName(array $middlewares)
    {
        $byName = [];

        foreach ($middlewares as $string) {
            $parts = explode(static::$middlewareDelimiter, $string, 2);
            $byName[$parts[0]] = isset($parts[1]) ? $parts[1] : '';
        }

        return $byName;
    }

    /**
     * Build the middleware signature out of its name and parameters.
     *
     * @param string $name
     * @param string $parameters
     * @return string
     */
    protected function signature($name, $parameters='')
    {
        return $parameters ? $name . static::$middlewareDelimiter . $parameters : $name;
    }

    /**
     * @param string $pattern
     *
     * @return string
     */
    protected function pattern($pattern)
    {
        return isset($this->common[Router::PREFIX]) ? $this->common[Router::PREFIX].$pattern : $pattern;
    }

    /**
     * @param string $handler
     *
     * @return string|object
     */
    protected function handler($handler)
    {
        if (!is_string($handler)) {
            return $handler;
        }
        return isset($this->common[Router::CONTROLLER]) ? $this->common[Router::CONTROLLER].static::$methodSeparator.$handler : $handler;
    }
}
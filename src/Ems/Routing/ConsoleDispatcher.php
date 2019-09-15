<?php
/**
 *  * Created by mtils on 15.09.19 at 14:58.
 **/

namespace Ems\Routing;


use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\RouteHit;

class ConsoleDispatcher implements Dispatcher
{
    /**
     * @var array
     */
    private $routesByPattern = [];

    /**
     * Add a route (definition). Whatever you put into it as $handler you will
     * get returned in match.
     *
     * @param string $method
     * @param string $pattern
     * @param mixed $handler
     */
    public function add($method, $pattern, $handler)
    {
        $this->routesByPattern[$pattern] = $handler;
    }

    /**
     * Find the handler for $method and $uri that someone did add().
     *
     * @param string $method
     * @param string $uri
     *
     * @return RouteHit
     */
    public function match($method, $uri)
    {
        if (!isset($this->routesByPattern[$uri])) {
            throw new RouteNotFoundException("No route did match $uri");
        }
        return new RouteHit($method, $uri, $this->routesByPattern[$uri]);
    }

    /**
     * Fill the interpreter with route definitions that he did export by toArray()
     *
     * @param array $data
     *
     * @return bool
     */
    public function fill(array $data)
    {
        $this->routesByPattern = $data;
        return true;
    }

    /**
     * Render an uri by the route pattern and parameters.
     *
     * @param string $pattern
     * @param array $parameters (optional)
     *
     * @return string
     */
    public function compile($pattern, array $parameters = [])
    {
        return $pattern . ' ' . implode(' ', $parameters);
    }

    /**
     * {inheritDoc}
     *
     * @return array
     **/
    public function toArray()
    {
        RETURN $this->routesByPattern;
    }

}
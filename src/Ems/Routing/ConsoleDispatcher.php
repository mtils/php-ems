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
     * @var string
     */
    private $fallbackCommand = '';

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

        if (!$uri && $this->fallbackCommand) {
            $uri = $this->fallbackCommand;
        }

        if (isset($this->routesByPattern[$uri])) {
            return new RouteHit($method, $uri, $this->routesByPattern[$uri]);
        }

        if (!$uri) {
            throw new RouteNotFoundException("No uri (command) passed and no fallback command set.");
        }

        throw new RouteNotFoundException("No route did match $uri");

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
    public function path($pattern, array $parameters = [])
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

    /**
     * @return string
     */
    public function getFallbackCommand()
    {
        return $this->fallbackCommand;
    }

    /**
     * Set a command that will be executed if none was passed.
     * (Something like your command list)
     *
     * @param string $fallbackCommand
     *
     * @return ConsoleDispatcher
     */
    public function setFallbackCommand($fallbackCommand)
    {
        $this->fallbackCommand = $fallbackCommand;
        return $this;
    }


}
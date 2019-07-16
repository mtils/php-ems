<?php
/**
 *  * Created by mtils on 30.06.19 at 10:54.
 **/

namespace Ems\Contracts\Routing;


use IteratorAggregate;

interface Dispatcher extends IteratorAggregate
{
    /**
     * @param callable $registrar
     */
    public function register(callable $registrar);

    /**
     * @param string $method
     * @param string $path
     * @param string $clientType
     * @param string $scope
     *
     * @return Routable
     */
    public function dispatch($method, $path, $clientType=Routable::CLIENT_WEB, $scope='default');

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
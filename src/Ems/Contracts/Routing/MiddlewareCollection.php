<?php
/**
 *  * Created by mtils on 21.08.18 at 14:56.
 **/

namespace Ems\Contracts\Routing;


use Countable;
use Ems\Contracts\Core\ArrayData;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\Positioner;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Core\SupportsCustomFactory;
use IteratorAggregate;

/**
 * Interface MiddlewareCollection
 *
 * The MiddlewareCollection is a middleware stack. The array interface behaves
 * so that it returns $name=>$middleware.
 *
 * Middleware is either callable or a string (class/ioc container binding key).
 * Every middleware has to be named. Then you have control over the sequence by
 * using the before/after methods.
 * The name also allows to add the same class multiple times (with different
 * parameters)
 *
 * Use parameters($name) to get the assigned parameters.
 *
 * To position a middleware call add($name, $middleware)->before($otherName)
 * or add($name, $middleware)->after($otherName)
 *
 * Middleware signature is:
 *
 * function (Input $input, callable $next) {
 *
 * }
 *
 * If you support custom parameters just add it to the signature:
 *
 * function (Input $input, callable $next, $role, ...) {
 *
 * }
 *
 * If you want to work with the response call next middleware and work with the
 * response:
 *
 * function (Input $input, callable $next) {
 *     $response = $next($input);
 *     return $response;
 * }
 *
 * Otherwise just manipulate the $input.
 *
 * @package Ems\Contracts\Routing
 */
interface MiddlewareCollection extends ArrayData, Countable, IteratorAggregate, SupportsCustomFactory
{
    /**
     * Add a middleware to the stack.
     *
     * @param string          $name
     * @param callable|string $middleware
     * @param string|array    $parameters (optional)
     *
     * @return Positioner
     */
    public function add($name, $middleware, $parameters=null);

    /**
     * Run the middleware stack/pipe/queue.
     *
     * @param Input $input
     *
     * @return Response
     */
    public function __invoke(Input $input);

    /**
     * Return the middleware instance named $name. In opposite to offsetGet()
     * this method will make the instance if this is necessary. (If a string was
     * assigned). offsetGet() would just return the string.
     *
     * @param string $name
     *
     * @return callable
     */
    public function middleware($name);

    /**
     * Return the assigned parameters for middleware named $name.
     * @param string $name
     *
     * @return array
     */
    public function parameters($name);
}
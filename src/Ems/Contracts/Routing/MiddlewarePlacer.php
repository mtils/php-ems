<?php
/**
 *  * Created by mtils on 28.09.19 at 10:27.
 **/

namespace Ems\Contracts\Routing;


use Ems\Contracts\Core\Positioner;
use function array_merge;
use function call_user_func;
use function in_array;

/**
 * Class MiddlewarePlacer
 *
 * This object is a helper for the fluent syntax middleware registration.
 *
 * @package Ems\Contracts\Routing
 */
class MiddlewarePlacer extends Positioner
{
    /**
     * @var callable
     */
    protected $invoker;

    /**
     * @var callable
     */
    protected $middlewareReplacer;

    /**
     * @var string|callable
     */
    protected $middleware;


    /**
     * Positioner constructor.
     *
     * @param mixed $handle
     * @param callable $beforeCallback
     * @param callable $afterCallback
     * @param callable $invoker
     * @param callable $replacer
     */
    public function __construct($handle, callable $beforeCallback, callable $afterCallback, callable $invoker, callable $replacer
    ) {
        parent::__construct($handle, $beforeCallback, $afterCallback);
        $this->invoker = $invoker;
        $this->middlewareReplacer = $replacer;
    }

    /**
     * @param Input $input
     * @param callable $next
     * @param mixed ...$args
     * @return mixed
     */
    public function __invoke(Input $input, callable $next, ...$args)
    {
        $clientTypes = $this->handle['clientTypes'];
        $scopes = $this->handle['scopes'];

        if ($clientTypes && !in_array($input->getClientType(), $clientTypes)) {
            return $next($input);
        }

        if ($scopes && !in_array((string)$input->getRouteScope(), $scopes)) {
            return $next($input);
        }

        $args = array_merge([$this->handle['middleware'], $input, $next], $args);

        return call_user_func($this->invoker, ...$args);
    }

    /**
     * Apply the added middleware only for this client types
     *
     * @param string ...$type
     *
     * @return $this
     */
    public function clientType(...$type)
    {
        $this->handle['clientTypes'] = $type;
        call_user_func($this->middlewareReplacer, $this);
        return $this;
    }

    /**
     * Apply the added middleware only in this scopes.
     *
     * @param string ...$scope
     *
     * @return $this
     */
    public function scope(...$scope)
    {
        $this->handle['scopes'] = $scope;
        call_user_func($this->middlewareReplacer, $this);
        return $this;
    }
}
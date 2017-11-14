<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 09.11.17
 * Time: 09:03
 */

namespace Ems\Contracts\Core;


class ContainerCallable
{
    /**
     * @var IOCContainer
     */
    protected $container;

    /**
     * @var string
     */
    protected $abstract;

    /**
     * @var string
     */
    protected $method = '';

    /**
     * @var bool
     */
    protected $useAppCall = false;

    /**
     * @var bool
     */
    protected $useParametersInResolve = false;

    /**
     * ContainerCallable constructor.
     *
     * @param IOCContainer $container
     * @param string       $abstract
     * @param bool         $useParametersInResolve (default:false)
     */
    public function __construct(IOCContainer $container, $abstract, $useParametersInResolve=false)
    {
        $this->container = $container;
        $this->abstract = $abstract;
        $this->useParametersInResolve = $useParametersInResolve;
    }

    /**
     * Call the container or the resolved instance.
     *
     * @return mixed
     */
    public function __invoke()
    {
        $args = func_get_args();

        $instance = $this->container->__invoke(
            $this->abstract,
            $this->useParametersInResolve ? $args : []
        );

        if (!$this->method) {
            return $instance;
        }

        if ($this->useAppCall) {
            return $this->container->call([$instance, $this->method], $args);
        }

        return $instance->{$this->method}(...$args);
    }

    /**
     * Determine which method should be called on the resolved instance.
     *
     * @example App::provide(UserController::class)->index()
     *
     * @param string $method
     * @param array $args (optional)
     *
     * @return $this
     */
    public function __call($method, array $args=[])
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Determine which method should be called on the resolved instance AND that
     * this method will be called with App::call().
     *
     * @param string $method
     *
     * @return self
     */
    public function call($method)
    {
        $this->method = $method;
        $this->useAppCall = true;
        return $this;
    }

    /**
     * Return if the container should process the arguments you give in __invoke.
     *
     * @return bool
     */
    public function shouldUseParametersInResolve()
    {
        return $this->useParametersInResolve;
    }

    /**
     * Return the method which should be called after resolving the $abstract.
     *
     * @return string
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * Return if this Callable should use App::call() to call the assigned
     * method.
     *
     * @return bool
     */
    public function shouldUseAppCall()
    {
        return $this->useAppCall;
    }

    /**
     * Force this callable to use App::call instead of calling the instance
     * directly.
     *
     * @param bool $use (default:true)
     * @return $this
     */
    public function useAppCall($use=true)
    {
        $this->useAppCall = $use;
        return $this;
    }
}
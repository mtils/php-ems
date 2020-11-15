<?php

namespace Ems\Core\Support;

use Ems\Contracts\Core\IOCContainer;
use OutOfBoundsException;
use Psr\Container\ContainerInterface;

trait IOCContainerProxyTrait
{
    use IOCHelperMethods;

    /**
     * @var IOCContainer
     **/
    protected $container;

    /**
     * @see IOCContainer::make()
     *
     * @param string $abstract
     *
     * @return object
     * @throws OutOfBoundsException
     *
     */
    public function make(string $abstract)
    {
        return $this->container->make($abstract);
    }

    /**
     * @see IOCContainer::create()
     *
     * @param string $abstract
     * @param array $parameters (optional)
     * @param bool $useExactClass (default: false)
     *
     * @return object
     */
    public function create(string $abstract, array $parameters = [], bool $useExactClass = false)
    {
        return $this->container->create($abstract, $parameters, $useExactClass);
    }

    /**
     * @see IOCContainer::bind()
     *
     * @param string          $abstract
     * @param callable|string $factory
     * @param bool            $singleton (optional)
     *
     * @return IOCContainer
     **/
    public function bind(string $abstract, $factory, bool $singleton = false)
    {
        return $this->container->bind($abstract, $factory, $singleton);
    }

    /**
     * @see IOCContainer::instance()
     *
     * @param string $abstract
     * @param object $instance
     *
     * @return IOCContainer
     **/
    public function instance(string $abstract, $instance)
    {
        return $this->container->instance($abstract, $instance);
    }

    /**
     * @see IOCContainer::resolving()
     *
     * @param string          $abstract
     * @param callable|string $listener
     *
     * @return IOCContainer
     **/
    public function resolving(string $abstract, $listener)
    {
        return $this->container->resolving($abstract, $listener);
    }

    /**
     * @see IOCContainer::afterResolving()
     *
     * @param string          $abstract
     * @param callable|string $listener
     *
     * @return IOCContainer
     **/
    public function afterResolving(string $abstract, $listener)
    {
        return $this->container->afterResolving($abstract, $listener);
    }

    /**
     * @see ContainerInterface::has()
     *
     * @param string $abstract
     *
     * @return bool
     * @noinspection PhpMissingParamTypeInspection
     */
    public function has($abstract)
    {
        return $this->container->has($abstract);
    }

    /**
     * @param string $abstract
     *
     * @deprecated use has($abstract)
     *
     * @return bool
     **/
    public function bound($abstract)
    {
        return $this->container->bound($abstract);
    }

    /**
     * @see IOCContainer::resolved()
     *
     * @param string $abstract
     *
     * @return bool
     **/
    public function resolved(string $abstract)
    {
        return $this->container->resolved($abstract);
    }

    /**
     * @see IOCContainer::call()
     *
     * @param callable $callback
     * @param array    $parameters
     *
     * @return mixed The method result
     **/
    public function call(callable $callback, array $parameters = [])
    {
        return $this->container->call($callback, $parameters);
    }

    /**
     * @see IOCContainer::alias()
     *
     * @param string $abstract
     * @param string $alias
     *
     * @return IOCContainer
     **/
    public function alias(string $abstract, string $alias)
    {
        return $this->container->alias($abstract, $alias);
    }

    /**
     * Get the assigned container.
     *
     * @return IOCContainer
     **/
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the container.
     *
     * @param IOCContainer $container
     *
     * @return self
     **/
    public function setContainer(IOCContainer $container)
    {
        $this->container = $container;

        return $this;
    }
}

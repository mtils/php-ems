<?php

namespace Ems\Core\Support;

use Ems\Contracts\Core\IOCContainer;

trait IOCContainerProxyTrait
{
    use IOCHelperMethods;

    /**
     * @var \Ems\Contracts\Core\IOCContainer
     **/
    protected $container;

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param array  $parameters (optional)
     *
     * @throws \OutOfBoundsException
     *
     * @return object
     **/
    public function __invoke($abstract, array $parameters = [])
    {
        return $this->container->__invoke($abstract, $parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @param string   $abstract
     * @param callable $callback
     * @param bool     $singleton (optional)
     *
     * @return self
     **/
    public function bind($abstract, $factory, $singleton = false)
    {
        return $this->container->bind($abstract, $factory, $singleton);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param object $instance
     *
     * @return self
     **/
    public function instance($abstract, $instance)
    {
        return $this->container->instance($abstract, $instance);
    }

    /**
     * {@inheritdoc}
     *
     * @param string   $abstract
     * @param callable $listener
     *
     * @return self
     **/
    public function resolving($abstract, $listener)
    {
        return $this->container->resolving($abstract, $listener);
    }

    /**
     * {@inheritdoc}
     *
     * @param string   $abstract
     * @param callable $listener
     *
     * @return self
     **/
    public function afterResolving($abstract, $listener)
    {
        return $this->container->afterResolving($abstract, $listener);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     *
     * @return bool
     **/
    public function bound($abstract)
    {
        return $this->container->bound($abstract);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     *
     * @return bool
     **/
    public function resolved($abstract)
    {
        return $this->container->resolved($abstract);
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $callback
     * @param array    $parameters
     *
     * @return mixed The method result
     **/
    public function call($callback, array $parameters = [])
    {
        return $this->container->call($callback, $parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param string $alias
     *
     * @return self
     **/
    public function alias($abstract, $alias)
    {
        return $this->container->alias($abstract, $alias);
    }

    /**
     * Get the assigned container.
     *
     * @return \Ems\Contracts\Core\IOCContainer
     **/
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the container.
     *
     * @param \Ems\Contracts\Core\IOCContainer $container
     *
     * @return self
     **/
    public function setContainer(IOCContainer $container)
    {
        $this->container = $container;

        return $this;
    }
}

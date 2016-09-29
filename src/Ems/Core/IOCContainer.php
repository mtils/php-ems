<?php


namespace Ems\Core;

use Ems\Contracts\Core\IOCContainer as ContainerContract;
use Ems\Core\Support\ResolvingListenerTrait;
use Ems\Core\Support\IOCHelperMethods;
use InvalidArgumentException;
use Ems\Core\Exceptions\NotImplementedException;
use ReflectionClass;


class IOCContainer implements ContainerContract
{

    use ResolvingListenerTrait;
    use IOCHelperMethods;

    /**
     * @var array
     **/
    protected $bindings = [];

    /**
     * @var array
     **/
    protected $aliases = [];

    /**
     * @var array
     **/
    protected $sharedInstances = [];

    /**
     * @var array
     **/
    protected $resolvedAbstracts = [];

    public function __construct()
    {
        $this->instance('Ems\Contracts\Core\IOCContainer', $this);
        $this->instance('Ems\Core\IOCContainer', $this);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param array $parameters (optional)
     * @return object
     * @throws \OutOfBoundsException
     **/
    public function __invoke($abstract, array $parameters=[])
    {
        if (isset($this->sharedInstances[$abstract])) {
            return $this->sharedInstances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            return $this->resolve($abstract, $parameters);
        }

        $concrete = $this->resolve($abstract, $parameters);

        $this->callAllListeners($abstract, $concrete);

        if ($this->bindings[$abstract]['shared']) {
            $this->sharedInstances[$abstract] = $concrete;
        }

        $this->resolvedAbstracts[$abstract] = true;

        return $concrete;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param callable $callback
     * @param bool $singleton (optional)
     * @return self
     **/
    public function bind($abstract, $callback, $singleton=false)
    {
        return $this->storeBinding($abstract, $callback, $singleton);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param object $instance
     * @return self
     **/
    public function instance($abstract, $instance)
    {
        $this->sharedInstances[$abstract] = $instance;

        // This will never be called, but makes resolved, bound etc. easier
        $this->storeBinding($abstract, function($container) use ($instance) {
            return $instance;
        }, true);

        $this->callAllListeners($abstract, $instance);

        return $this;

    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param callable $listener
     * @return self
     **/
    public function resolving($abstract, $listener)
    {
        return $this->storeResolvingListener($abstract, $listener);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param callable $listener
     * @return self
     **/
    public function afterResolving($abstract, $listener)
    {
        return $this->storeAfterResolvingListener($abstract, $listener);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @return bool
     **/
    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @return bool
     **/
    public function resolved($abstract)
    {
        return isset($this->resolvedAbstracts[$abstract]) || isset($this->sharedInstances[$abstract]);
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $callback
     * @return mixed The method result
     **/
    public function call($callback, array $parameters=[])
    {

        switch (count($parameters)) {
            case 0:
                return call_user_func($callback);
            case 1:
                return call_user_func($callback, $parameters[0]);
            case 2:
                return call_user_func($callback, $parameters[0], $parameters[1]);
            case 3:
                return call_user_func($callback, $parameters[0], $parameters[1], $parameters[2]);
            case 4:
                return call_user_func($callback, $parameters[0], $parameters[1], $parameters[2], $parameters[3]);
        }

        return call_user_func_array($callback, $parameters);

    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param string $alias
     * @return self
     **/
    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $abstract;
        return $this;
    }

    /**
     * Resolves the $abstract via assigned bindings
     *
     * @param string $abstract
     * @param array $parameters (optional)
     * @return object
     **/
    protected function resolve($abstract, array $parameters=array())
    {

        if ($this->bound($abstract)) {
            array_unshift($parameters, $this);
            return call_user_func_array($this->bindings[$abstract]['concrete'], $parameters);
        }

        $reflector = new ReflectionClass($abstract);

        if (!$constructor = $reflector->getConstructor()) {
            return new $abstract($parameters);
        }

        $constructorParams = $constructor->getParameters();

        $callParams = array();

        foreach ($constructorParams as $param) {
            if ($param->getClass()) {
                $callParams[] = $this->__invoke($param->getClass()->getName());
            }
        }

        foreach ($parameters as $key=>$value) {
            $callParams[] = $value;
        }

        return $reflector->newInstanceArgs($callParams);

    }

    /**
     * Stores the binding inside the bindings
     *
     * @param string abstract
     * @param callable|object $concrete
     * @param bool $shared
     * @return self
     **/
    protected function storeBinding($abstract, $concrete, $shared)
    {
        $this->bindings[$abstract] = array(
            'concrete' => $this->checkAndReturnCallable($concrete),
            'shared' => $shared
        );
        return $this;
    }

    /**
     * Throws an exception if the arg is not callable
     *
     * @param callable $callback
     * @return callable
     * @throws InvalidArgumentException
     **/
    protected function checkAndReturnCallable($callback)
    {
        if (is_string($callback)) {
            return function ($app) use ($callback) {
                return $app($callback);
            };
        }

        if (!is_callable($callback)) {
            $type = is_object($callback) ? get_class($callback) : gettype($callback);
            throw new InvalidArgumentException("Passed argument of type $type is not callable");
        }
        return $callback;
    }

}
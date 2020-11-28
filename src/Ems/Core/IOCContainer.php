<?php

namespace Ems\Core;

use Ems\Contracts\Core\IOCContainer as ContainerContract;
use Ems\Core\Exceptions\BindingNotFoundException;
use Ems\Core\Exceptions\IOCContainerException;
use Ems\Core\Support\IOCHelperMethods;
use Ems\Core\Support\ResolvingListenerTrait;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function call_user_func;
use function get_class;
use function is_string;

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

    /**
     * @var array
     */
    protected static $reflectionClasses = [];

    public function __construct()
    {
        $this->instance('Ems\Contracts\Core\IOCContainer', $this);
        $this->instance('Ems\Core\IOCContainer', $this);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     *
     * @return object
     * @throws IOCContainerException
     * @deprecated use self::get()
     */
    public function make(string $abstract)
    {
        return $this->get($abstract);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed Entry.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @noinspection PhpMissingParamTypeInspection
     */
    public function get($id)
    {
        if (isset($this->sharedInstances[$id])) {
            return $this->sharedInstances[$id];
        }

        $bound = isset($this->bindings[$id]);

        try {
            $concrete = $this->makeOrCreate($id);
        } catch (Exception $e) {
            if (!$this->has($id)) {
                throw new BindingNotFoundException("Binding $id not found");
            }
            throw new IOCContainerException("Error building $id", 0, $e);
        }

        if ($bound && $this->bindings[$id]['shared']) {
            $this->sharedInstances[$id] = $concrete;
        }

        $this->resolvedAbstracts[$id] = true;

        return $concrete;
    }


    /**
     * {@inheritDoc}
     *
     * @param string $abstract
     * @param array $parameters (optional)
     * @param bool $useExactClass (default: false)
     *
     * @return object
     * @throws ReflectionException
     */
    public function create(string $abstract, array $parameters = [], bool $useExactClass = false)
    {
        $implementation = $useExactClass ? $abstract : $this->getAliasOrSame($abstract);

        if (!$useExactClass && isset($this->bindings[$implementation]) && $this->bindings[$implementation]['concreteClass']) {
            $implementation = class_exists($this->bindings[$implementation]['concreteClass']) ? $this->bindings[$implementation]['concreteClass'] : $implementation;
        }

        if (!isset(self::$reflectionClasses[$implementation])) {
            self::$reflectionClasses[$implementation] = new ReflectionClass($implementation);
        }

        /** @var ReflectionMethod $constructor */
        if (!$constructor = self::$reflectionClasses[$implementation]->getConstructor()) {
            return $this->callAllListeners($abstract, new $implementation($parameters));
        }

        $constructorParams = $constructor->getParameters();

        $callParams = [];

        // All parameters seems to be passed
        if (count($constructorParams) == count($parameters)) {
            return self::$reflectionClasses[$implementation]->newInstanceArgs($parameters);
        }

        foreach ($constructorParams as $i=>$param) {

            $name = $param->getName();

            if (isset($parameters[$name])) {
                $callParams[] = $parameters[$name];
                continue;
            }

            if (!$class = $param->getClass()) {
                continue;
            }

            $className = $class->getName();

            if (isset($parameters[$i]) && $parameters[$i] instanceof $className) {
                $callParams[] = $parameters[$i];
                continue;
            }

            if (!$param->isOptional() || $this->bound($className)) {
                $callParams[] = $this->__invoke($className);
            }
        }

        foreach ($parameters as $key => $value) {
            $callParams[] = $value;
        }

        $object = self::$reflectionClasses[$implementation]->newInstanceArgs($callParams);

        return $this->callAllListeners($abstract, $object);

    }

    /**
     * {@inheritdoc}
     *
     * @param string          $abstract
     * @param callable|string $callback
     * @param bool            $singleton (optional)
     *
     * @return self
     **/
    public function bind(string $abstract, $callback, bool $singleton = false)
    {
        return $this->storeBinding($abstract, $callback, $singleton);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param object $instance
     *
     * @return self
     **/
    public function instance(string $abstract, $instance)
    {
        $this->sharedInstances[$abstract] = $instance;

        // This will never be called, but makes resolved, has etc. easier
        $this->storeBinding($abstract, function () use ($instance) {
            return $instance;
        }, true);

        $this->callAllListeners($abstract, $instance);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string          $abstract
     * @param callable|string $listener
     *
     * @return self
     **/
    public function resolving(string $abstract, $listener)
    {
        return $this->storeResolvingListener($abstract, $listener);
    }

    /**
     * {@inheritdoc}
     *
     * @param string          $abstract
     * @param callable|string $listener
     *
     * @return self
     **/
    public function afterResolving(string $abstract, $listener)
    {
        return $this->storeAfterResolvingListener($abstract, $listener);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     * @noinspection PhpMissingParamTypeInspection
     */
    public function has($id)
    {
        return isset($this->bindings[$id]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     *
     * @deprecated use has($abstract)
     *
     * @return bool
     **/
    public function bound($abstract)
    {
        return $this->has($abstract);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     *
     * @return bool
     **/
    public function resolved(string $abstract)
    {
        return isset($this->resolvedAbstracts[$abstract]) || isset($this->sharedInstances[$abstract]);
    }

    /**
     * {@inheritdoc}
     *
     * You can either pass numeric parameters to insert not injected params
     * positional or pass an assoc array with $parameterName=>$value to manually
     * inject a view parameters on your own.
     * Passing BOTH (numeric and assoc arrays) is not supported.
     *
     * @param callable $callback
     *
     * @return mixed The method result
     *
     * @throws ReflectionException
     */
    public function call(callable $callback, array $parameters = [])
    {

        $argsReflection = Lambda::reflect($callback);
        $args = [];

        foreach ($argsReflection as $name=>$info) {

            // If someone manually added the parameter by name just use that
            if (isset($parameters[$name])) {
                $args[$name] = $parameters[$name];
                continue;
            }

            if (!$info['type']) {
                continue;
            }

            if (!$info['optional']) {
                $args[$name] = $this->make($info['type']);
            }

        }

        // If no args were built and no or numeric parameters were passed
        // Take the fast version
        if (!$args && (isset($parameters[0]) || !$parameters)) {
            return call_user_func($callback, ...$parameters);
        }

        $merged = Lambda::mergeArguments($argsReflection, $args, isset($parameters[0]) ? $parameters : []);
        return Lambda::call($callback, $merged);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param string $alias
     *
     * @return self
     **/
    public function alias(string $abstract, string $alias)
    {
        $this->aliases[$alias] = $abstract;

        return $this;
    }

    /**
     * @param string $abstract
     *
     * @return object
     * @throws ReflectionException
     */
    protected function makeOrCreate(string $abstract)
    {
        $abstract = $this->getAliasOrSame($abstract);

        if (!$this->has($abstract)) {
            return $this->create($abstract);
        }

        $object = call_user_func($this->bindings[$abstract]['concrete'], $this);

        return $this->callAllListeners($abstract, $object);
    }

    /**
     * Stores the binding inside the bindings.
     *
     * @param string          $abstract
     * @param callable|string $concrete
     * @param bool            $shared
     *
     * @return self
     **/
    protected function storeBinding($abstract, $concrete, $shared)
    {
        $this->bindings[$abstract] = [
            'concrete'      => $this->checkAndReturnCallable($concrete),
            'shared'        => $shared,
            'concreteClass' => is_string($concrete) ? $concrete : null
        ];

        return $this;
    }

    /**
     * Throws an exception if the arg is not callable.
     *
     * @param callable|string $callback
     *
     * @throws InvalidArgumentException
     *
     * @return callable
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

    /**
     * @param string $abstract
     *
     * @return string
     */
    protected function getAliasOrSame($abstract)
    {
        return isset($this->aliases[$abstract]) ? $this->getAliasOrSame($this->aliases[$abstract]) : $abstract;
    }
}

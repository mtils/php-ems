<?php

namespace Ems\Core\Laravel;

use Closure;
use Ems\Contracts\Core\IOCContainer as ContainerContract;
use Ems\Core\Exceptions\BindingNotFoundException;
use Ems\Core\Exceptions\IOCContainerException;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Ems\Core\Support\IOCHelperMethods;
use Ems\Core\Support\ResolvingListenerTrait;
use Exception;
use Illuminate\Container\Container as IlluminateContainer;
use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;

use function get_class;
use function is_callable;
use function is_string;

class IOCContainer implements ContainerContract
{
    use ResolvingListenerTrait;
    use IOCHelperMethods;

    /**
     * @var IlluminateContainer
     **/
    protected $laravel;

    /**
     * @param IlluminateContainer|null $laravel
     **/
    public function __construct(IlluminateContainer $laravel = null)
    {
        $this->laravel = $laravel ? $laravel : new IlluminateContainer();
        $this->instance('Illuminate\Contracts\Container\Container', $this->laravel);
        $this->instance('Illuminate\Container\Container', $this->laravel);
        $this->instance(ContainerContract::class, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     *
     * @return object
     * @throws OutOfBoundsException
     * @deprecated use self::get() or laravel directly
     *
     */
    public function make(string $abstract)
    {
        return $this->laravel->make($abstract);
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
        try {
            return $this->laravel->make($id);
        } catch (Exception $e) {
            if (!$this->laravel->bound($id)) {
                throw new BindingNotFoundException("Binding $id not found", 0, $e);
            }
            throw new IOCContainerException("Error resolving $id", 0, $e);
        }
    }


    /**
     * {@inheritDoc}
     *
     * @param string $abstract
     * @param array $parameters (optional)
     * @param bool $useExactClass (default: false)
     *
     * @return object
     */
    public function create(string $abstract, array $parameters = [], bool $useExactClass = false)
    {
        if (!$parameters = $this->convertParameters($abstract, $parameters)) {
            $parameters = ['some_never_used_parameter_name' => true];
        }
        $result = $this->laravel->makeWith($abstract, $parameters);
        if ($useExactClass && !get_class($result) != $abstract) {
            throw new UnsupportedUsageException("Laravel backend does not support exact class enforcement");
        }
        return $result;
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
        $this->laravel->bind($abstract, $this->buildBindingProxy($callback), $singleton);
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $abstract
     * @param callable|string|null $factory
     *
     * @return void
     */
    public function share(string $abstract, $factory = null)
    {
        if (!$factory || is_string($factory)) {
            $this->laravel->singleton($abstract, $factory);
            return;
        }
        $this->bind($abstract, $factory, true);
    }


    /**
     * {@inheritdoc}
     * Laravel doesnt call its listeners on instance(), this class
     * emulates it for full compatiblity with the IOC interface.
     *
     * @param string $abstract
     * @param object $instance
     *
     * @return self
     **/
    public function instance(string $abstract, $instance)
    {
        $this->laravel->instance($abstract, $instance);
        $this->callAllListeners($abstract, $instance);

        return $this;
    }

    /**
     * {@inheritdoc}
     * Because laravel does not call listeners on instance() this
     * class has to store them too.
     *
     * @param string          $abstract
     * @param callable|string $listener
     *
     * @return self
     **/
    public function resolving(string $abstract, $listener)
    {
        $this->laravel->resolving($abstract, $this->buildResolvingCallable($listener));

        return $this->storeResolvingListener($abstract, $listener);
    }

    /**
     * {@inheritdoc}
     * Because laravel does not call listeners on instance() this
     * class has to store them too.
     *
     * @param string          $abstract
     * @param callable|string $listener
     *
     * @return self
     **/
    public function afterResolving(string $abstract, $listener)
    {
        $this->laravel->afterResolving($abstract, $this->buildResolvingCallable($listener));

        return $this->storeAfterResolvingListener($abstract, $listener);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return $this->laravel->bound($id);
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
        return $this->laravel->bound($abstract);
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
        return $this->laravel->resolved($abstract);
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $callback
     *
     * @return mixed The method result
     **/
    public function call(callable $callback, array $parameters = [])
    {
        return $this->laravel->call($callback, $parameters);
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
        $this->laravel->alias($abstract, $alias);

        return $this;
    }

    /**
     * @return IlluminateContainer
     **/
    public function laravel()
    {
        return $this->laravel;
    }

    /**
     * If you assign a callable to be called to create the desired binding this
     * container has to be passed to the callable, not laravels.
     *
     * So every binding have to be proxies through a Closure
     *
     * @param callable $originalCallable
     *
     * @return Closure
     **/
    protected function buildBindingProxy($originalCallable)
    {
        $originalCallable = $this->checkAndReturnCallable($originalCallable);

        return function () use ($originalCallable) {
            return call_user_func($originalCallable, $this);
        };
    }

    /**
     * If you assign a callable to be called when bindings get resolved this
     * container has to be passed to the callable, not laravels.
     *
     * So every resolving callback have to be proxies through a Closure
     *
     * @param callable $originalCallable
     *
     * @return Closure
     **/
    protected function buildResolvingCallable($originalCallable)
    {
        $originalCallable = $this->checkAndReturnCallable($originalCallable);

        return function ($resolved) use ($originalCallable) {
            call_user_func($originalCallable, $resolved, $this);
        };
    }

    /**
     * Throws an exception if the arg is not callable.
     *
     * @param callable $callback
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
     * @param mixed $abstract
     * @param array $parameters
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function convertParameters($abstract, array $parameters)
    {

        if (!$parameters) {
            return [];
        }

        $bindings = $this->laravel->getBindings();

        $concrete = isset($bindings[$abstract]) ? $bindings[$abstract]['concrete'] : $abstract;

        if ($concrete instanceof Closure) {
            return $parameters;
        }

        $constructor = (new ReflectionClass($concrete))->getConstructor();

        $named = [];

        foreach ($constructor->getParameters() as $i=>$parameter) {
            $name = $parameter->getName();
            if (isset($parameters[$i])) {
                $named[$name] = $parameters[$i];
            }
            if (isset($parameters[$name])) {
                $named[$name] = $parameters[$name];
            }
        }

        return $named;

    }
}

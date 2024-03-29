<?php

namespace Ems\Core\Laravel;

use Closure;
use Ems\Contracts\Core\ContainerCallable;
use Ems\Contracts\Core\IOCContainer as ContainerContract;
use Ems\Core\Exceptions\BindingNotFoundException;
use Ems\Core\Exceptions\IOCContainerException;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Ems\Core\Lambda;
use Ems\Core\Patterns\ListenerContainer;
use Ems\Core\Support\IOCHelperMethods;
use Exception;
use Illuminate\Container\Container as IlluminateContainer;
use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;

use function get_class;
use function is_callable;
use function is_object;
use function is_string;

class IOCContainer implements ContainerContract
{
    use IOCHelperMethods;

    /**
     * @var IlluminateContainer
     **/
    protected $laravel;

    /**
     * @var ListenerContainer
     */
    protected $listeners;

    /**
     * @param IlluminateContainer|null $laravel
     **/
    public function __construct(IlluminateContainer $laravel = null)
    {
        $this->laravel = $laravel ? $laravel : new IlluminateContainer();
        $this->listeners = new ListenerContainer();
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
    public function get(string $id)
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
     * emulates it for full compatibility with the IOC interface.
     *
     * @param string $abstract
     * @param object $instance
     *
     * @return self
     **/
    public function instance(string $abstract, $instance)
    {
        $this->laravel->instance($abstract, $instance);

        $this->listeners->callByInheritance($abstract, $instance, [$instance, $this], ListenerContainer::POSITIONS);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * CAUTION There is no difference between on and onBefore with laravel container.
     *
     * @param string|object $event
     * @param callable $listener
     *
     * @return self
     **/
    public function onBefore($event, callable $listener)
    {
        $abstract = is_object($event) ? get_class($event) : $event;
        $this->laravel->resolving($abstract, $this->buildResolvingCallable($listener));
        $this->listeners->add($abstract, $listener, ListenerContainer::BEFORE);
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param string|object $event
     * @param callable $listener
     *
     * @return self
     **/
    public function on($event, callable $listener)
    {
        return $this->onBefore($event, $listener);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|object $event
     * @param callable $listener
     *
     * @return self
     **/
    public function onAfter($event, callable $listener)
    {
        $abstract = is_object($event) ? get_class($event) : $event;
        $this->laravel->afterResolving($abstract, $this->buildResolvingCallable($listener));
        $this->listeners->add($abstract, $listener, ListenerContainer::AFTER);
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param string|object $event
     * @param string $position ('after'|'before'|'')
     *
     * @return array
     **/
    public function getListeners($event, $position = '')
    {
        if (!$position) {
            return [];
        }
        $abstract = is_object($event) ? get_class($event) : $event;
        return $this->listeners->get($abstract, $position);
    }


    /**
     * {@inheritDoc}
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id) : bool
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
        // Seems to be not indexed or empty parameters
        if (!isset($parameters[0])) {
            return $this->laravel->call($callback, $parameters);
        }
        $argReflection = Lambda::reflect($callback);
        $namedArgs = [];
        $i=0;
        foreach ($argReflection as $argumentName=>$info) {
            if ($info['type'] && $this->laravel->bound($info['type'])) {
                continue;
            }
            $namedArgs[$argumentName] = $parameters[$i];
            $i++;
        }
        return $this->laravel->call($callback, $namedArgs);

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
     * {@inheritdoc}
     * Because laravel does not call listeners on instance() this
     * class has to store them too.
     *
     * @param string          $abstract
     * @param callable|string $listener
     *
     * @return self
     * @deprecated use self::on() or laravel directly
     **/
    public function resolving(string $abstract, $listener)
    {
        return $this->on($abstract, $listener);
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
     * @deprecated use self::onAfter() or laravel directly
     **/
    public function afterResolving(string $abstract, $listener)
    {
        return $this->onAfter($abstract, $listener);
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

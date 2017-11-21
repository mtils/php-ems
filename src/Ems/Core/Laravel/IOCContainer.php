<?php

namespace Ems\Core\Laravel;

use Closure;
use InvalidArgumentException;
use Illuminate\Container\Container as IlluminateContainer;
use Ems\Contracts\Core\IOCContainer as ContainerContract;
use Ems\Core\Support\ResolvingListenerTrait;
use Ems\Core\Support\IOCHelperMethods;
use Ems\Testing\Cheat;
use ReflectionClass;
use ReflectionParameter;

class IOCContainer implements ContainerContract
{
    use ResolvingListenerTrait;
    use IOCHelperMethods;

    /**
     * @var \Illuminate\Container\Container
     **/
    protected $laravel;

    /**
     * @param \Illuminate\Container\Container $laravel
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
     * @param array  $parameters (optional)
     *
     * @throws \OutOfBoundsException
     *
     * @return object
     **/
    public function __invoke($abstract, array $parameters = [])
    {
        if (!$parameters) {
            return $this->laravel->make($abstract);
        }

        // Laravel 5.4
        if (method_exists($this->laravel, 'makeWith')) {
            return $this->laravel->makeWith($abstract, $this->convertParameters($abstract, $parameters));
        }

        return $this->laravel->make($abstract, $parameters);
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
    public function bind($abstract, $callback, $singleton = false)
    {
        $this->laravel->bind($abstract, $this->buildBindingProxy($callback), $singleton);

        return $this;
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
    public function instance($abstract, $instance)
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
     * @param string   $abstract
     * @param callable $listener
     *
     * @return self
     **/
    public function resolving($abstract, $listener)
    {
        $this->laravel->resolving($abstract, $this->buildResolvingCallable($listener));

        return $this->storeResolvingListener($abstract, $listener);
    }

    /**
     * {@inheritdoc}
     * Because laravel does not call listeners on instance() this
     * class has to store them too.
     *
     * @param string   $abstract
     * @param callable $listener
     *
     * @return self
     **/
    public function afterResolving($abstract, $listener)
    {
        $this->laravel->afterResolving($abstract, $this->buildResolvingCallable($listener));

        return $this->storeAfterResolvingListener($abstract, $listener);
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
        return $this->laravel->bound($abstract);
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
        return $this->laravel->resolved($abstract);
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $callback
     *
     * @return mixed The method result
     **/
    public function call($callback, array $parameters = [])
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
    public function alias($abstract, $alias)
    {
        $this->laravel->alias($abstract, $alias);

        return $this;
    }

    /**
     * @return \Illuminate\Container\Container
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

        return function ($laravel) use ($originalCallable) {
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

        return function ($resolved, $laravel) use ($originalCallable) {
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
     **/
    protected function convertParameters($abstract, array $parameters)
    {

        // TODO Ugly hack to get the concrete class in laravel
        $concrete = Cheat::call($this->laravel, 'getConcrete', [$abstract]);

        if ($concrete instanceof Closure) {
            return $parameters;
        }

        $constructor = (new ReflectionClass($concrete))->getConstructor();

        $named = [];

        foreach ($constructor->getParameters() as $i=>$parameter) {
            if (isset($parameters[$i])) {
                $named[$parameter->getName()] = $parameters[$i];
            }
        }

        return $named;

    }
}

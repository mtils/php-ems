<?php

namespace Ems\Contracts\Core;

interface IOCContainer
{

    /**
     * Make a class/abstract definition / Like laravel Container::make()
     * I prefer to have not typehinted factories because the caller mostly needs
     * a one-method object what can be just a callable. So if you need a factory,
     * typehint against callable and inject the Container.
     * The first parameter of this callable has to be the $abstract.
     * If you dont like to pass the $abstract to a callable use self::provide()
     *
     * @param string $abstract
     * @param array $parameters (optional)
     * @return object
     * @throws \OutOfBoundsException
     **/
    public function __invoke($abstract, array $parameters=[]);

    /**
     * Alias for self::__invoke
     *
     * @param string $abstract
     * @param array $parameters (optional)
     * @return object
     * @throws \OutOfBoundsException
     **/
    public function make($abstract, array $parameters=[]);

    /**
     * If you have methods to lazyload a class I prefer to use a simple
     * callable. Like this: $request->provideRoute(callable provider)
     * The provide method returns a callable which will resolve
     * the $abstract you pass to the provide method.
     * If you resolve a class through this method you can pass
     * $processParameters=true. Then the callable parameters will be
     * passed to the class constructor
     *
     * @param string $abstract
     * @param bool $processParameters (optional)
     * @return \Closure
     **/
    public function provide($abstract, $processParameters=false);

    /**
     * Bind a callable to create $abstract if requested. If the $abstract should
     * be resolved only once, pass $singleton=true
     *
     * @param string $abstract
     * @param callable $callback
     * @param bool $singleton (optional)
     * @return self
     **/
    public function bind($abstract, $factory, $singleton=false);

    /**
     * Share an instance. If you already created whatever you will use for $abstract
     * register it via this method
     *
     * @param string $abstract
     * @param object $instance
     * @return self
     **/
    public function instance($abstract, $instance);

    /**
     * Register a listener which will get called if $abstract was resolved
     *
     * @param string $abstract
     * @param callable $listener
     * @return self
     **/
    public function resolving($abstract, $listener);

    /**
     * Register a listener which will get called if $abstract was resolved and
     * resolving() listeners were called
     *
     * @param string $abstract
     * @param callable $listener
     * @return self
     **/
    public function afterResolving($abstract, $listener);

    /**
     * Check if $abstract was is (via bind, share, shareInstance or alias)
     *
     * @param string $abstract
     * @return bool
     **/
    public function bound($abstract);

    /**
     * Check if $abstract was resolved (via __invoke)
     *
     * @param string $abstract
     * @return bool
     **/
    public function resolved($abstract);

    /**
     * Call $callback with the given parameters. If the callback contains
     * typehinted params, resolve them an inject them
     *
     * @param callable $callback
     * @param array $parameters
     * @return mixed The method result
     **/
    public function call($callback, array $parameters=[]);

    /**
     * Alias a type. When you call this method you say:
     * If someone asks for $alias, create the object bound for $abstract
     * Or you could also say: Use $abstract also for $alias
     *
     * @param string $abstract
     * @param string $alias
     * @return self
     **/
    public function alias($abstract, $alias);

}
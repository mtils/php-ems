<?php

namespace Ems\Contracts\Core;

use OutOfBoundsException;
use Psr\Container\ContainerInterface;

/**
 * Interface IOCContainer
 *
 * The ems container tries to fulfill the following main principles:
 * -> The dependency of the built/resolved classes have to be zero
 * -> The application building/orchestration classes must have full control
 *    over bindings, resolving
 * -> Differentiate between usage as a factory vs. di container
 *
 * This interface assumes that you mainly use interface names as keys. Sometimes
 * classes.
 * It appears to me PSR-11 sees that differently.
 *
 * So here are the recommendations:
 *
 * 1. outside bootstrapping (or SupportsCustomFactory) use $ioc() (__invoke)
 * and do not require the interface at all.
 * Passing parameters to that method means "create a new object, I use you as a
 * factory". This is mostly not the case.
 *
 * 2. inside bootstrapping: If you just need to get an object from the application
 * use make(). If the container has a factory for it it uses it, otherwise
 * it just creates it (and inject its dependencies). Like make in laravel but
 * without parameters.
 *
 * 3. inside bootstrapping: If you need to create a new instance of an object no
 * matter if they perhaps are registered as singletons for the same identifier
 * use create(). This is often useful if you use classes in different configurations
 * but have one main configuration.
 *
 * 4. inside bootstrapping: If you want to have an object and you force that it
 * has to have a factory for it and it must not be created automatically and
 * you are not willed to call has($binding) before you use PSR get() method.
 * (in my usage of a di container not so handy but it is important to support
 * standards)
 *
 * @package Ems\Contracts\Core
 */
interface IOCContainer extends ContainerInterface, Subscribable, Hookable
{
    /**
     * Make a class/abstract definition / Like laravel Container::make()
     * I prefer to have not type-hinted factories because the caller mostly needs
     * a one-method object what can be just a callable. So if you need a factory,
     * typehint against callable and inject the Container.
     * The first parameter of this callable has to be the $abstract.
     * If you dont like to pass the $abstract to a callable use self::provide().
     *
     * @param string $abstract
     * @param array  $parameters (optional - means I want to create a new object)
     *
     * @return object
     *
     * @noinspection PhpMissingParamTypeInspection
     * @throws OutOfBoundsException
     */
    public function __invoke($abstract, array $parameters = []);

    /**
     * Create the object of class $abstract. Inject parameters that were not
     * passed in $parameters. If a binding remaps $abstract to a different
     * abstract use this. Pass $useExactClass to force to use exactly $abstract
     * and not any rebound class name.
     *
     * @param string    $abstract
     * @param array     $parameters (optional)
     * @param bool      $useExactClass (default: false)
     *
     * @return object
     */
    public function create(string $abstract, array $parameters=[], bool $useExactClass=false);

    /**
     * If you have methods to lazy-load a class I prefer to use a simple
     * callable. Like this: $request->provideRoute(callable provider)
     * The provide method returns a callable which will resolve
     * the $abstract you pass to the provide method.
     *
     * You can also make a method call provider by just passing a method name
     * as the second parameter.
     *
     * @param string $abstract
     * @param string $method (optional)
     *
     * @return ContainerCallable
     **/
    public function provide(string $abstract, string $method = '');

    /**
     * Bind a callable to create $abstract if requested. If the $abstract should
     * be resolved only once, pass $singleton=true.
     *
     * @param string            $abstract
     * @param callable|string   $factory
     * @param bool              $singleton (optional)
     *
     * @return self
     **/
    public function bind(string $abstract, $factory, bool $singleton = false);

    /**
     * Create a shared binding (singleton). Omit the factory to let the container
     * create one for you.
     *
     * @param string               $abstract
     * @param callable|string|null $factory
     *
     * @return void
     */
    public function share(string $abstract, $factory=null);

    /**
     * Share an instance. If you already created whatever you will use for $abstract
     * register it via this method.
     *
     * @param string $abstract
     * @param object $instance
     *
     * @return self
     **/
    public function instance(string $abstract, $instance);

    /**
     * Check if $abstract was resolved via get or __invoke without parameters.
     * It is not true if there is no binding for $abstract and $abstract was
     * built by the container itself.
     *
     * @param string $abstract
     *
     * @return bool
     **/
    public function resolved(string $abstract);

    /**
     * Call $callback with the given parameters. If the callback contains
     * type-hinted params, resolve them an inject them.
     *
     * @param callable $callback
     * @param array    $parameters
     *
     * @return mixed The method result
     **/
    public function call(callable $callback, array $parameters = []);

    /**
     * Alias a type. When you call this method you say:
     * If someone asks for $alias, create the object bound for $abstract
     * Or you could also say: Use $abstract also for $alias.
     *
     * @param string $abstract
     * @param string $alias
     *
     * @return self
     **/
    public function alias(string $abstract, string $alias);

}

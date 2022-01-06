<?php

namespace Ems\Skeleton;

use Ems\Contracts\Core\IOCContainer;
use Ems\Skeleton\Application as BaseApplication;

/**
 * The Bootmanager is the place where to boot your app startup classes
 * and closures.
 * Either you add a class to be loaded at boot via add($class) or
 * just pass some callable to be called at boot.
 *
 * The Bootmanager has 3 phases while booting:
 * 1. Package bindings
 * 2. App bindings
 * 3. Booting
 *
 * If you pass something in the provides array, the loading of the $class
 * will be deffered until some binding of this array was requested
 * Deferred loading is only supported by classes (add)
 */
class BootManager
{
    /**
     * @var Application
     **/
    protected $app;

    /**
     * @var IOCContainer
     **/
    protected $container;

    /**
     * @var array
     **/
    protected $classes = [];

    /**
     * @var array
     **/
    protected $instances = [];

    /**
     * @var array
     **/
    protected $packageBinders = [];

    /**
     * @var array
     **/
    protected $binders = [];

    /**
     * @var array
     **/
    protected $booters = [];

    /**
     * @var array
     **/
    protected static $configurators = [];

    /**
     * @var bool
     **/
    protected $configuratorsCalled = false;

    /**
     * @param IOCContainer $container
     **/
    public function __construct(IOCContainer $container)
    {
        $this->container = $container;
        // Make it instantly a singleton
        $this->container->instance(self::class, $this);
    }

    /**
     * @return Application
     **/
    public function getApplication() : Application
    {
        return $this->app;
    }

    /**
     * @param Application $app
     *
     * @return self
     **/
    public function setApplication(Application $app) : BootManager
    {

        if ($app->wasBooted()) {
            throw new \RuntimeException('App was already booted. To late for Bootmanager to hook into boot.');
        }

        $this->app = $app;

        $this->app->instance('bootManager', $this);

        $app->onBefore('boot', function (Application $app) {
            $this->bindPackages();
            $this->bind();
            $this->boot();
        });

        return $this;
    }

    /**
     * Add a class to be booted / Registered. The class can have
     * the following methods: bindPackages, bind and boot
     * If some methods exists it will be called in the corresponding
     * boot phase.
     *
     * @param string|object $class
     * @param array         $provides (optional)
     *
     * @return self
     **/
    public function add($class, array $provides = []) : BootManager
    {
        $this->callConfiguratorsOnce();

        if (is_object($class)) {
            $this->classes[] = get_class($class);
            $this->instances[get_class($class)] = $class;

            return $this;
        }

        $this->classes[] = $class;

        return $this;
    }

    /**
     * Add a callable to bind package bindings, the first called bindings.
     *
     * @param string $name
     * @param callable $binder
     *
     * @return $this
     */
    public function addPackageBinder(string $name, callable $binder) : BootManager
    {
        $this->callConfiguratorsOnce();
        $this->packageBinders[$name] = $binder;

        return $this;
    }

    /**
     * Add a callable to bind your bindings, the second called bindings.
     *
     * @param string $name
     * @param callable $binder
     * @return $this
     */
    public function addBinder(string $name, callable $binder) : BootManager
    {
        $this->callConfiguratorsOnce();
        $this->binders[$name] = $binder;

        return $this;
    }

    /**
     * Add a callable to bind your booters, the third called bindings in boot process.
     *
     * @param string $name
     * @param callable $booter
     * @return $this
     */
    public function addBooter(string $name, callable $booter) : BootManager
    {
        $this->callConfiguratorsOnce();
        $this->booters[$name] = $booter;

        return $this;
    }

    public function bindPackages()
    {
        $this->callAll('bindPackages', $this->packageBinders);
    }

    public function bind()
    {
        $this->callAll('bind', $this->binders);
    }

    public function boot()
    {
        $this->callAll('boot', $this->booters);
    }

    /**
     * Do something before the first add/addBinder/... call
     * is invoked. The instantiated BootManager and the
     * container will be passed.
     *
     * @param callable $listener
     **/
    public static function configureBy(callable $listener)
    {
        static::$configurators[] = $listener;
    }

    protected function callAll($method, array $callables)
    {
        $this->callConfiguratorsOnce();

        foreach ($this->classes as $class) {
            $this->callIfExists($this->resolveOnce($class), $method);
        }
        foreach ($callables as $name => $binder) {
            $this->container->call($binder, [$this->container]);
        }
    }

    /**
     * Resolves the boot class once via the container.
     *
     * @param string $class
     *
     * @return object
     **/
    protected function resolveOnce(string $class)
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }
        $this->instances[$class] = $this->container->create($class);

        return $this->instances[$class];
    }

    /**
     * Calls the booter method if exists.
     *
     * @param object $booter
     * @param string $method
     **/
    protected function callIfExists($booter, $method)
    {
        if (method_exists($booter, $method)) {
            $this->container->call([$booter, $method], [$this->container]);
        }
    }

    /**
     * Calls the creation listeners.
     **/
    protected function callConfiguratorsOnce()
    {
        if ($this->configuratorsCalled) {
            return;
        }

        foreach (static::$configurators as $listener) {
            call_user_func($listener, $this, $this->container);
        }

        $this->configuratorsCalled = true;
    }
}

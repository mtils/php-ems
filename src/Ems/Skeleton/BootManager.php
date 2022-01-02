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
     * @var \Ems\Skeleton\Application
     **/
    protected $ems;

    /**
     * @var \Ems\Contracts\Core\IOCContainer
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
     * @return \Ems\Skeleton\Application
     **/
    public function getApplication()
    {
        return $this->ems;
    }

    /**
     * @param \Ems\Skeleton\Application $ems
     *
     * @return self
     **/
    public function setApplication(BaseApplication $ems)
    {

        if ($ems->wasBooted()) {
            throw new \RuntimeException('App was already booted. To late for Bootmanager to hook into boot.');
        }

        $this->ems = $ems;

        $this->ems->instance('bootManager', $this);

        $ems->onBefore('boot', function ($ems) {
            $this->bindPackages($ems);
            $this->bind($ems);
            $this->boot($ems);
        });

        return $this;
    }

    /**
     * Add a class to be booted / Registered. The class can have
     * the following methods: bindPackages, bind and boot
     * If some of the methods exists it will be called in the corresponding
     * boot phase.
     *
     * @param string|object $class
     * @param array         $provides (optional)
     *
     * @return self
     **/
    public function add($class, $provides = [])
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

    public function addPackageBinder($name, callable $binder)
    {
        $this->callConfiguratorsOnce();
        $this->packageBinders[$name] = $binder;

        return $this;
    }

    public function addBinder($name, callable $binder)
    {
        $this->callConfiguratorsOnce();
        $this->binders[$name] = $binder;

        return $this;
    }

    public function addBooter($name, callable $booter)
    {
        $this->callConfiguratorsOnce();
        $this->booters[$name] = $booter;

        return $this;
    }

    public function bindPackages(IOCContainer $container)
    {
        $this->callAll('bindPackages', $this->packageBinders);
    }

    public function bind(IOCContainer $container)
    {
        $this->callAll('bind', $this->binders);
    }

    public function boot(IOCContainer $container)
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
    protected function resolveOnce($class)
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }
        $this->instances[$class] = call_user_func($this->container, $class);

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

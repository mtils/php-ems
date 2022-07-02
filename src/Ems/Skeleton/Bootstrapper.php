<?php

namespace Ems\Skeleton;

use Ems\Contracts\Core\IOCContainer;
use Ems\Contracts\Routing\Router;

use function defined;
use function function_exists;
use function realpath;
use function spl_object_hash;

class Bootstrapper
{
    /**
     * Put your bindings here,
     * leads to $app->bind($value, $app->get($key)) !
     *
     * @example [
     *   'MyRouter' => 'RouterInterface',
     *   'MyConfig' => ['ConfigInterface', 'app.config'] // alias
     * ]
     *
     * @var array
     **/
    protected $bindings = [];

    /**
     * Put your singletons here, leads to $app->singleton($val, $app->get($key)).
     *
     * @see self::bindings
     *
     * @var array
     **/
    protected $singletons = [];

    /**
     * Put your aliases here ([$alias] => [$abstract,$abstract2].
     *
     * @var array
     **/
    protected $aliases = [];

    /**
     * @var IOCContainer
     */
    protected $container;

    /**
     * @var Application
     **/
    protected $app;

    /**
     * A hash table to store which routers were configured.
     *
     * @var array
     */
    protected $configuredRouters = [];

    /**
     * @var callable[]
     */
    protected $routeLoaders = [];

    /**
     * @param IOCContainer $container
     */
    public function __construct(IOCContainer $container)
    {
        $this->assignApplication($container);
        $this->container = $this->app->getContainer();
    }

    public function bind()
    {
        $this->registerAliases();
        $this->bindBindings();
        $this->bindSingletons();
    }

    /**
     * @param callable $adder
     */
    protected function addRoutesBy(callable $adder)
    {
        $this->routeLoaders[] = $adder;
        $this->container->onAfter(Router::class, function (Router $router) use ($adder) {
            $routerId = spl_object_hash($router);
            if (isset($this->configuredRouters[$routerId])) {
                return;
            }
            $this->configuredRouters[$routerId] = true;
            $router->register($adder);
        });
    }

    /**
     * Registers all in $this->aliases.
     **/
    protected function registerAliases()
    {
        foreach ($this->aliases as $abstract => $aliases) {
            foreach ((array) $aliases as $alias) {
                $this->container->alias($alias, $abstract);
            }
        }
    }

    /**
     * Registers all in $this->bindings.
     **/
    protected function bindBindings()
    {
        foreach ($this->bindings as $concrete => $abstracts) {
            if (!is_array($abstracts)) {
                $this->container->bind($abstracts, $concrete);
                continue;
            }

            $first = array_shift($abstracts);

            $this->container->bind($first, $concrete);

            foreach ($abstracts as $abstract) {
                $this->container->alias($first, $abstract);
            }
        }
    }

    /**
     * Registers all in $this->singletons.
     **/
    protected function bindSingletons()
    {
        foreach ($this->singletons as $concrete => $abstracts) {
            if (!is_array($abstracts)) {
                $this->container->bind($abstracts, $concrete, true);
                continue;
            }

            $first = array_shift($abstracts);

            $this->container->bind($first, $concrete, true);

            foreach ($abstracts as $abstract) {
                $this->container->alias($first, $abstract);
            }
        }
    }

    protected function assignApplication(IOCContainer $app)
    {
        if ($app instanceof Application) {
            $this->app = $app;
            return;
        }
        if ($app->has(Application::class)) {
            $this->app = $app->get(Application::class);
            return;
        }
        $this->app = $this->createPlaceholderApp($app);
        $app->instance(Application::class, $this->app);

    }

    /**
     * @param IOCContainer $container
     * @return \Ems\Skeleton\Application
     */
    protected function createPlaceholderApp(IOCContainer $container) : Application
    {
        return new Application($this->appPath(), $container, false);
    }

    /**
     * Return the applications base path (the vcs root directory)
     *
     * @return string
     **/
    protected function appPath() : string
    {
        if (defined('APP_ROOT')) {
            return APP_ROOT;
        }
        if (isset($_ENV['APP_BASE_PATH'])) {
            return $_ENV['APP_BASE_PATH'];
        }
        if (function_exists('app_path')) {
            return app_path();
        }
        // Try to guess relative from vendor
        return realpath(__DIR__.'/../../../../..');
    }
}

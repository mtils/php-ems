<?php

namespace Ems\Core\Skeleton;

use Ems\Contracts\Core\IOCContainer;
use Ems\Contracts\Routing\Router;

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
     * @var \Ems\Contracts\Core\IOCContainer
     **/
    protected $app;

    /**
     * A hash table to store which routers were configured.
     *
     * @var array
     */
    protected $configuredRouters = [];

    /**
     * @param \Ems\Contracts\Core\IOCContainer $app
     **/
    public function __construct(IOCContainer $app)
    {
        $this->app = $app;
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
        $this->app->onAfter(Router::class, function (Router $router) use ($adder) {
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
                $this->app->alias($alias, $abstract);
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
                $this->app->bind($abstracts, $concrete);
                continue;
            }

            $first = array_shift($abstracts);

            $this->app->bind($first, $concrete);

            foreach ($abstracts as $abstract) {
                $this->app->alias($first, $abstract);
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
                $this->app->bind($abstracts, $concrete, true);
                continue;
            }

            $first = array_shift($abstracts);

            $this->app->bind($first, $concrete, true);

            foreach ($abstracts as $abstract) {
                $this->app->alias($first, $abstract);
            }
        }
    }
}

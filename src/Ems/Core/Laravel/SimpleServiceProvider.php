<?php

namespace Ems\Core\Laravel;

use Illuminate\Support\ServiceProvider;

/**
 * This is just a simple helper class to avoid code duplication of
 * app->bind($bla, function($app){}) Stuff
 **/
abstract class SimpleServiceProvider extends ServiceProvider
{

    /**
     * Put your bindings here,
     * leads to $app->bind($value, $app->make($key)) !
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
     * Put your singletons here, leads to $app->singleton($val, $app->make($key))
     *
     * @see self::bindings
     * @var array
     **/
    protected $singletons = [];

    /**
     * Put your aliases here ([$alias] => [$abstract,$abstract2]
     *
     * @var array
     **/
    protected $aliases = [];

    public function register()
    {
        $this->registerAliases();
        $this->registerBindings();
        $this->registerSingletons();
    }

    /**
     * Registers all in $this->aliases
     *
     * @return null
     **/
    protected function registerAliases()
    {
        foreach ($this->aliases as $abstract=>$aliases) {
            foreach ((array)$aliases as $alias) {
                $this->app->alias($alias, $abstract);
            }
        }
    }

    /**
     * Registers all in $this->bindings
     *
     * @return null
     **/
    protected function registerBindings()
    {
        foreach ($this->bindings as $concrete=>$abstracts) {

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
     * Registers all in $this->singletons
     *
     * @return null
     **/
    protected function registerSingletons()
    {
        foreach ($this->singletons as $concrete=>$abstracts) {

            if (!is_array($abstracts)) {
                $this->app->singleton($abstracts, $concrete);
                continue;
            }

            $first = array_shift($abstracts);

            $this->app->singleton($first, $concrete);

            foreach ($abstracts as $abstract) {
                $this->app->alias($first, $abstract);
            }
        }

    }

}

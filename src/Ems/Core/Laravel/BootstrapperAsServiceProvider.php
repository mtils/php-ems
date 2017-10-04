<?php

namespace Ems\Core\Laravel;

use Illuminate\Support\ServiceProvider;
use Ems\Contracts\Core\IOCContainer as Ems;

abstract class BootstrapperAsServiceProvider extends ServiceProvider
{
    /**
     * @var object
     **/
    protected $bootstrapper;

    /**
     * @var Ems
     **/
    protected $ems;

    /**
     * Create a new service provider instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct($app)
    {
        parent::__construct($app);

        if (!$app->bound(Ems::class)) {
            $app->instance(Ems::class, new IOCContainer($app));
        }
        $this->ems = $app->make(Ems::class);
    }

    /**
     * Return the name of the bootstrapper class.
     *
     * @return string
     **/
    abstract protected function bootClass();

    /**
     * Return the created bootstrapper.
     *
     * @return object
     **/
    protected function bootstrapper()
    {
        if (!$this->bootstrapper) {
            $this->bootstrapper = $this->app->make($this->bootClass());
        }

        return $this->bootstrapper;
    }

    public function register()
    {
        $this->callIfExists('bind');
    }

    public function boot()
    {
        $this->callIfExists('boot');
    }

    protected function callIfExists($method)
    {
        $bootstrapper = $this->bootstrapper();

        if (method_exists($bootstrapper, $method)) {
            $this->app->call([$bootstrapper, $method]);
        }
    }
}

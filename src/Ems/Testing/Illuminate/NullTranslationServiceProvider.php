<?php


namespace Ems\Testing\Illuminate;

use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;

/**
 * Use this service provider to register an translator without
 * all the config stuff and other dependencies you dont have in a library
 **/
class NullTranslationServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLoader();

        $this->app->singleton('translator', function ($app) {
            return new Translator($app['translation.loader'], 'en');
        });
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new ArrayLoader();
        });
    }

}

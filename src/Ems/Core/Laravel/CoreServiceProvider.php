<?php

namespace Ems\Core\Laravel;

use Ems\Contracts\Core\TextProvider;
use Ems\Skeleton\CoreBootstrapper;

class CoreServiceProvider extends BootstrapperAsServiceProvider
{
    protected function bootClass()
    {
        return CoreBootstrapper::class;
    }

    public function register()
    {

        parent::register();

        // Overwrite TextProvider with the translator textProvider
        $this->app->singleton(TextProvider::class, TranslatorTextProvider::class);

    }
}

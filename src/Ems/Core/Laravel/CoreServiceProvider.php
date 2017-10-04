<?php

namespace Ems\Core\Laravel;

use Ems\Contracts\Core\TextProvider;
use Ems\Core\Laravel\TranslatorTextProvider;

class CoreServiceProvider extends BootstrapperAsServiceProvider
{
    protected function bootClass()
    {
        return 'Ems\Core\Skeleton\CoreBootstrapper';
    }

    public function register()
    {

        parent::register();

        // Overwrite TextProvider with the translator textProvider
        $this->app->singleton(TextProvider::class, TranslatorTextProvider::class);

    }
}

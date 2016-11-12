<?php

namespace Ems\Assets\Laravel;

use Ems\Core\Laravel\BootstrapperAsServiceProvider;

class AssetsServiceProvider extends BootstrapperAsServiceProvider
{
    protected function bootClass()
    {
        return 'Ems\Assets\Skeleton\AssetsBootstrapper';
    }

    public function register()
    {
        parent::register();
        $this->commands([
             'Ems\Assets\Symfony\ListBuildConfigurationsCommand',
             'Ems\Assets\Symfony\CompileCommand',
        ]);
    }
}

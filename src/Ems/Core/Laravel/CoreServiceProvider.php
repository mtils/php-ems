<?php

namespace Ems\Core\Laravel;


class CoreServiceProvider extends BootstrapperAsServiceProvider
{

    protected function bootClass()
    {
        return 'Ems\Core\Skeleton\CoreBootstrapper';
    }

}

<?php

namespace Ems\Foundation\Illuminate;

use Ems\Core\Laravel\BootstrapperAsServiceProvider;
use Ems\Foundation\Skeleton\FoundationBootstrapper;

class FoundationServiceProvider extends BootstrapperAsServiceProvider
{
    /**
     * @return string
     **/
    protected function bootClass()
    {
        return FoundationBootstrapper::class;
    }
}

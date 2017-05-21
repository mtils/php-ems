<?php

namespace Ems\XType\Illuminate;

use Ems\Core\Laravel\BootstrapperAsServiceProvider;
use Ems\XType\Skeleton\XTypeBootstrapper;

class XTypeServiceProvider extends BootstrapperAsServiceProvider
{
    /**
     * @return string
     **/
    protected function bootClass()
    {
        return XTypeBootstrapper::class;
    }
}

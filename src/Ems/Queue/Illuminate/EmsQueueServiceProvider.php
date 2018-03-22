<?php
/**
 *  * Created by mtils on 04.02.18 at 06:12.
 **/

namespace Ems\Queue\Illuminate;


use Ems\Contracts\Queue\Driver;
use Ems\Core\Laravel\BootstrapperAsServiceProvider;
use Ems\Queue\Skeleton\QueueBootstrapper;

class EmsQueueServiceProvider extends BootstrapperAsServiceProvider
{
    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    protected function bootClass()
    {
        return QueueBootstrapper::class;
    }

    public function register()
    {
        parent::register();
        $this->app->bind(Driver::class, QueueDriver::class);

    }
}
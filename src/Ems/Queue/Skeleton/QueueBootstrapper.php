<?php
/**
 *  * Created by mtils on 04.02.18 at 06:02.
 **/

namespace Ems\Queue\Skeleton;


use Ems\Core\Skeleton\Bootstrapper;
use Ems\Contracts\Queue\Queue as QueueContract;
use Ems\Contracts\Queue\Tasker as TaskerContract;
use Ems\Queue\Queue;
use Ems\Queue\Tasker;

class QueueBootstrapper extends Bootstrapper
{
    /**
     * @var array
     **/
    protected $singletons = [
        Queue::class            => QueueContract::class,
        Tasker::class           => TaskerContract::class
    ];
}
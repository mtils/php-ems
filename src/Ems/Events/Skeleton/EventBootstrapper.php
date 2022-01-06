<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 09.11.17
 * Time: 19:49
 */

namespace Ems\Events\Skeleton;


use Ems\Skeleton\Bootstrapper;
use Ems\Contracts\Events\Bus as BusContract;
use Ems\Events\Bus;

class EventBootstrapper extends Bootstrapper
{
    public function bind()
    {
        parent::bind();

        // I dont know why, but binding it normally with laravel
        // leads to endless recursion
        $this->container->bind(BusContract::class, function () {
            return new Bus;
        }, true);
    }
}
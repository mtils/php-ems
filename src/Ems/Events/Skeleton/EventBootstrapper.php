<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 09.11.17
 * Time: 19:49
 */

namespace Ems\Events\Skeleton;


use Ems\Core\Skeleton\Bootstrapper;
use Ems\Contracts\Events\Bus as BusContract;
use Ems\Events\Bus;

class EventBootstrapper extends Bootstrapper
{
    protected $singletons = [
        //Bus::class  =>  BusContract::class
    ];

    public function bind()
    {
        parent::bind();

        // I dont know why, but binding it normally with laravel
        // leads to endless recursion
        $this->app->bind(BusContract::class, function () {
            return new Bus;
        }, true);
    }
}
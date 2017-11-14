<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 12.11.17
 * Time: 11:20
 */

namespace Ems\Http\Skeleton;


use Ems\Contracts\Core\Url;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Contracts\Core\ConnectionPool;
use Ems\Contracts\Http\Client as ClientContract;
use Ems\Contracts\Http\Connection as HttpConnection;
use Ems\Http\Client;
use Ems\Http\FilesystemConnection;

class HttpBootstrapper extends Bootstrapper
{
    protected $singletons = [
        Client::class => ClientContract::class
    ];

    public function bind()
    {
        parent::bind();

        $this->app->afterResolving(ConnectionPool::class, function (ConnectionPool $pool) {

            $pool->extend('http', function (Url $url) {
                if ($url->scheme == 'http' || $url->scheme == 'https') {
                    return new FilesystemConnection($url);
                }
            });
        });

    }
}
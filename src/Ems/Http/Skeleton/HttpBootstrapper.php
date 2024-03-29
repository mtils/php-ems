<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 12.11.17
 * Time: 11:20
 */

namespace Ems\Http\Skeleton;


use Ems\Contracts\Core\Url;
use Ems\Skeleton\Bootstrapper;
use Ems\Contracts\Core\ConnectionPool;
use Ems\Contracts\Http\Client as ClientContract;
use Ems\Contracts\Http\Connection as HttpConnection;
use Ems\Http\Client;
use Ems\Http\FilesystemConnection;

class HttpBootstrapper extends \Ems\Skeleton\Bootstrapper
{
    protected $singletons = [
        Client::class => ClientContract::class
    ];

    /**
     * @return void
     */
    public function bind()
    {
        parent::bind();

        $this->container->onAfter(ConnectionPool::class, function (ConnectionPool $pool) {

            $pool->extend('http', function (Url $url) {
                if ($url->scheme == 'http' || $url->scheme == 'https') {
                    return $this->container->create(FilesystemConnection::class, [$url]);
                }
                return null;
            });
        });

    }
}
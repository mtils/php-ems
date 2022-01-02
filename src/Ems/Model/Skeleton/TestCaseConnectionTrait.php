<?php
/**
 *  * Created by mtils on 21.11.2021 at 11:58.
 **/

namespace Ems\Model\Skeleton;

use Ems\Contracts\Core\ConnectionPool as ConnectionPoolContract;
use Ems\Contracts\Model\Database\Connection;
use Ems\Skeleton\Application;
use Ems\Core\ConnectionPool;
use Ems\Core\Url;
use Ems\Model\Database\Dialects\SQLiteDialect;
use Ems\Model\Database\PDOConnection;

trait TestCaseConnectionTrait
{
    /**
     * @var Connection
     */
    public static $con;

    public function afterBootTestCaseConnection(Application $app)
    {
        /** @var ConnectionPoolContract $pool */
        $pool = $app->get(ConnectionPoolContract::class);
        $newPool = new ConnectionPool();

        $defaultConnectionName = $app->config('database')['connection'];


        $newPool->extend('database.tests', function ($name) use ($defaultConnectionName) {

            if ($name instanceof Url && $name->scheme == 'database') {
                $name = $name->host;
            }

            if ($name == 'default' || $name == $defaultConnectionName) {
                return static::$con;
            }

            return null;
        });

        $newPool->extend('original-pool', function ($name) use ($pool) {
            return $pool->connection($name);
        });

        $app->instance(ConnectionPoolContract::class, $newPool);

    }

    /**
     * @beforeClass
     */
    public static function openConnection()
    {
        $dialect = new SQLiteDialect();
        static::$con = new PDOConnection(new Url('sqlite://memory'));
        static::$con->setDialect($dialect);
    }

    /**
     * @afterClass
     */
    public static function closeConnection()
    {
        static::$con->close();
    }
}
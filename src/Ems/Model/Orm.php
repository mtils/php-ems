<?php
/**
 *  * Created by mtils on 13.04.20 at 06:53.
 **/

namespace Ems\Model;

use Ems\Contracts\Core\ConnectionPool;
use Ems\Contracts\Core\ObjectArrayConverter;
use Ems\Contracts\Core\ObjectDataAdapter;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Model\OrmQueryRunner;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Contracts\Model\Database\Connection as DBConnection;

class Orm
{
    /**
     * @var ConnectionPool
     */
    private $connections;

    /**
     * @var SchemaInspector
     */
    private $inspector;

    /**
     * @var ObjectArrayConverter
     */
    private $objectFactory;

    /**
     * @param string $class
     *
     * @return OrmQuery
     */
    public function query($class)
    {
        $query = new OrmQuery($class);

        $url = $this->inspector->getStorageUrl($class);

        $connection = $this->connections->connection($url);

        $query->setRunner($this->runner($url))
            ->setConnection($connection)
            ->setObjectFactory($this->objectFactory);

        return $query;

    }

    /**
     * @param Url $url
     *
     * @return OrmQueryRunner
     */
    public function runner(Url $url)
    {

    }

    /**
     * @param string $class
     *
     * @return ObjectDataAdapter
     */
    public function objectFactory($class)
    {

    }
}
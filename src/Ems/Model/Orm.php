<?php
/**
 *  * Created by mtils on 13.04.20 at 06:53.
 **/

namespace Ems\Model;

use Ems\Contracts\Core\ConnectionPool;
use Ems\Contracts\Core\ObjectDataAdapter;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Model\Database\OrmQueryBuilder as QueryBuilderContract;
use Ems\Contracts\Model\OrmQueryRunner;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Contracts\Model\Database\Connection as DBConnection;

class Orm
{
    /**
     * @var QueryBuilderContract
     */
    private $builder;

    /**
     * @var ConnectionPool
     */
    private $connections;

    /**
     * @var SchemaInspector
     */
    private $inspector;

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

        $runner = $this->runner($url);

        $resultWithArrays = $runner->retrieve($connection, $query);

        $converter = $this->objectFactory($url);

        foreach ($resultWithArrays as $array) {
            //$class = $this->inspector->getRelation()
            $object = $converter->fromArray($array, true);
        }

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
     * @param $url
     *
     * @return ObjectDataAdapter
     */
    public function objectFactory($url)
    {

    }
}
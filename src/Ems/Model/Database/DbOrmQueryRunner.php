<?php
/**
 *  * Created by mtils on 13.04.20 at 08:34.
 **/

namespace Ems\Model\Database;


use Ems\Contracts\Core\Connection;
use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Model\Database\Connection as DbConnection;
use Ems\Contracts\Model\OrmQuery;
use Ems\Contracts\Model\OrmQueryRunner;
use Ems\Contracts\Model\Result;
use Ems\Contracts\Model\SchemaInspector;

class DbOrmQueryRunner implements OrmQueryRunner
{
    /**
     * @var SchemaInspector
     */
    private $inspector;

    /**
     * @var \Ems\Contracts\Model\Database\OrmQueryBuilder
     */
    private $builder;

    /**
     * @param Connection $connection
     * @param OrmQuery $query
     *
     * @return Result
     */
    public function retrieve(Connection $connection, OrmQuery $query)
    {
        $dbQuery = $this->db($connection)->query($this->inspector->getStorageName($query->ormClass));
        // Perhaps no interface for DbOrmQueryBuilder
        $this->builder->toSelect($query, $dbQuery);
        // TODO CAUTION! flat database rows have to be converted to multidimensional array!
        return $dbQuery;
    }

    public function create(Connection $connection, array $values)
    {
        // TODO: Implement create() method.
    }

    public function update(Connection $connection, OrmQuery $query, array $values)
    {
        // TODO: Implement update() method.
    }

    public function delete(Connection $connection, OrmQuery $query)
    {
        // TODO: Implement delete() method.
    }

    /**
     * @param Connection $connection
     *
     * @return DbConnection
     */
    protected function db(Connection $connection)
    {
        if (!$connection instanceof DbConnection) {
            throw new TypeException('I only work with connections of type ' . DbConnection::class);
        }
        return $connection;
    }

}
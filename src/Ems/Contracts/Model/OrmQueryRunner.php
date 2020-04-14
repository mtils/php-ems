<?php
/**
 *  * Created by mtils on 13.04.20 at 08:14.
 **/

namespace Ems\Contracts\Model;


use Ems\Contracts\Core\Connection;

interface OrmQueryRunner
{
    /**
     * @param Connection $connection
     * @param OrmQuery $query
     *
     * @return Result
     */
    public function retrieve(Connection $connection, OrmQuery $query);

    public function create(Connection $connection, array $values);

    public function update(Connection $connection, OrmQuery $query, array $values);

    public function delete(Connection $connection, OrmQuery $query);
}
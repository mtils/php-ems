<?php
/**
 *  * Created by mtils on 13.04.20 at 08:14.
 **/

namespace Ems\Contracts\Model;


use Ems\Contracts\Core\Connection;

interface OrmQueryRunner
{
    /**
     * Select / get results.
     *
     * @param Connection $connection
     * @param OrmQuery $query
     *
     * @return Result
     */
    public function retrieve(Connection $connection, OrmQuery $query);

    /**
     * Create one dataset of $ormClass. Return the inserted or generated id(s).
     * To retrieve all updated values (with auto timestamps etc.) you have to
     * manually retrieve to avoid unneeded queries.
     * If you get all values automatically by your storage (like often in a rest
     * api) cache it to return it on a following retrieve call.
     *
     * @param Connection      $connection
     * @param string|OrmQuery $ormClass
     * @param array           $values
     *
     * @return int|string|array
     */
    public function create(Connection $connection, $ormClass, array $values);

    /**
     * Update ALL matching objects that match $query by $values. Return the count
     * of affected objects.
     * If you cannot estimate the effect throw an exception. In most cases the
     * query is just for one or many ids. If your backend only supports single
     * updates you could only accept updates by one or a few ids.
     *
     * @param Connection $connection
     * @param OrmQuery   $query
     * @param array      $values
     *
     * @return int
     */
    public function update(Connection $connection, OrmQuery $query, array $values);

    /**
     * Delete ALL matching objects of query. See the doc block of update() for
     * more information. Return the count of deleted rows.
     *
     * @param Connection $connection
     * @param OrmQuery $query
     *
     * @return int
     */
    public function delete(Connection $connection, OrmQuery $query);
}
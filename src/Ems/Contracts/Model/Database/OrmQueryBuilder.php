<?php

/**
 *  * Created by mtils on 11.04.20 at 09:16.
 **/

namespace Ems\Contracts\Model\Database;

use Ems\Contracts\Model\OrmQuery;

interface OrmQueryBuilder
{
    /**
     * Create a new orm query.
     *
     * @param string $class
     *
     * @return OrmQuery
     */
    public function query($class);

    /**
     * @param OrmQuery $query
     * @param Query    $useThis (optional)
     *
     * @return Query
     */
    public function toSelect(OrmQuery $query, Query $useThis=null);

    /**
     * @param OrmQuery $query
     * @param array    $values
     * @param Query    $useThis (optional)
     *
     * @return Query
     */
    public function toInsert(OrmQuery $query, array $values, Query $useThis=null);

    /**
     * @param OrmQuery $query
     * @param array    $values
     * @param Query    $useThis (optional)
     *
     * @return Query
     */
    public function toUpdate(OrmQuery $query, array $values, Query $useThis=null);

    /**
     * @param OrmQuery $query
     * @param Query    $useThis (optional)
     *
     * @return Query
     */
    public function toDelete(OrmQuery $query, Query $useThis=null);
}
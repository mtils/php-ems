<?php
/**
 *  * Created by mtils on 15.02.20 at 07:52.
 **/

namespace Ems\Model\Database;

use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Model\Database\Connection as DBConnection;
use Ems\Contracts\Model\Database\Query as BaseQuery;
use Ems\Contracts\Model\PaginatableResult;
use Ems\Contracts\Pagination\Paginator as PaginatorContract;
use Ems\Core\Support\RenderableTrait;
use Ems\Model\ResultTrait;
use Iterator;
use Traversable;

/**
 * Class Query
 *
 * @package Ems\Model\Database
 */
class Query extends BaseQuery implements Renderable, PaginatableResult
{
    use RenderableTrait;
    use ResultTrait;

    /**
     * @var DBConnection
     */
    private $connection;

    /**
     * Returns the mimetype of this query. See RFC6922.
     *
     * @return string
     **/
    public function mimeType()
    {
        return 'application/sql';
    }

    /**
     * Retrieve the results from database...
     *
     * @return Iterator
     */
    public function getIterator()
    {
        // TODO: Implement getIterator() method.
    }

    /**
     * Paginate the result. Return whatever paginator you use.
     * The paginator should be \Traversable.
     *
     * @param int $page (optional)
     * @param int $perPage (optional)
     *
     * @return Traversable|array|PaginatorContract
     **/
    public function paginate($page = 1, $perPage = 15)
    {
        // TODO: Implement paginate() method.
    }

    /**
     * @param array $values (optional, otherwise use $this->values)
     * @param bool  $returnLastInsert (default:true)
     *
     * @return int
     */
    public function insert(array $values=[], $returnLastInsert=true)
    {

    }

    /**
     * Perform a REPLACE INTO | INSERT ON DUPLICATE KEY UPDATE query.
     *
     * @param array $values (optional, otherwise use $this->values)
     * @param bool  $returnAffected (default:true)
     *
     * @return int
     */
    public function replace(array $values=[], $returnAffected=true)
    {

    }

    /**
     * @param array $values (optional, otherwise use $this->values)
     *
     * @param bool $returnAffected (default:true)
     *
     * @return int
     */
    public function update(array $values=[], $returnAffected=true)
    {

    }

    /**
     * @param bool $returnAffected (default:true)
     *
     * @return int
     */
    public function delete($returnAffected=true)
    {

    }

    /**
     * @return DBConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param DBConnection $connection
     * @return Query
     */
    public function setConnection(DBConnection $connection)
    {
        $this->connection = $connection;
        return $this;
    }
}
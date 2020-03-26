<?php
/**
 *  * Created by mtils on 15.02.20 at 07:52.
 **/

namespace Ems\Model\Database;

use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Model\Database\Connection as DBConnection;
use Ems\Contracts\Model\Database\Query as BaseQuery;
use Ems\Contracts\Model\Database\SQLExpression;
use Ems\Contracts\Model\PaginatableResult;
use Ems\Contracts\Model\Result;
use Ems\Contracts\Pagination\Paginator as PaginatorContract;
use Ems\Core\Expression;
use Ems\Core\Support\RenderableTrait;
use Ems\Model\ResultTrait;
use Ems\Pagination\Paginator;
use Traversable;

use function call_user_func;
use function class_exists;
use function is_bool;

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
     * @var Query
     */
    private $countQuery;

    /**
     * @var callable
     */
    private $paginatorFactory;

    /**
     * This is just for tests.
     *
     * @var boolean
     */
    public static $paginatorClassExists;

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
     * @return Traversable
     * @throws \Exception
     */
    public function getIterator()
    {
        $this->operation = 'SELECT';
        return $this->readFromConnection($this)->getIterator();
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

        $result = $this->runPaginated($page, $perPage);

        if ($this->paginatorFactory) {
            return call_user_func($this->paginatorFactory, $result, $this, $page, $perPage);
        }

        if (!$this->paginatorClassExists()) {
            return $result;
        }

        $paginator = new Paginator($page, $perPage);
        return $paginator->setResult($result, $this->getTotalCount());
    }

    /**
     * Perform an INSERT query on the assigned connection.
     *
     * @param array $values (optional, otherwise use $this->values)
     * @param bool  $returnLastInsert (default:true)
     *
     * @return int
     */
    public function insert(array $values = [], $returnLastInsert = true)
    {
        return $this->writeToConnection('INSERT', $returnLastInsert, $values);
    }

    /**
     * Perform a REPLACE INTO | INSERT ON DUPLICATE KEY UPDATE query.
     *
     * @param array $values (optional, otherwise use $this->values)
     * @param bool  $returnAffected (default:true)
     *
     * @return int
     */
    public function replace(array $values = [], $returnAffected = true)
    {
        return $this->writeToConnection('REPLACE', $returnAffected, $values);
    }

    /**
     * Perform an update query.
     *
     * @param array $values (optional, otherwise use $this->values)
     * @param bool $returnAffected (default:true)
     *
     * @return int
     */
    public function update(array $values = [], $returnAffected = true)
    {
        return $this->writeToConnection('UPDATE', $returnAffected, $values);
    }

    /**
     * Perform a DELETE query.
     *
     * @param bool $returnAffected (default:true)
     *
     * @return int
     */
    public function delete($returnAffected = true)
    {
        return $this->writeToConnection('DELETE', $returnAffected);
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
     *
     * @return Query
     */
    public function setConnection(DBConnection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Get the assigned query to calculate the pagination total count.
     *
     * @return Query
     */
    public function getCountQuery()
    {
        return $this->countQuery;
    }

    /**
     * Set the query to calculate pagination total count.
     *
     * @param Query $countQuery
     *
     * @return Query
     */
    public function setCountQuery(Query $countQuery)
    {
        $this->countQuery = $countQuery;
        return $this;
    }

    /**
     * Assign a custom callable to create your desired paginator.
     *
     * @param callable $factory
     *
     * @return $this
     */
    public function createPaginatorBy(callable $factory)
    {
        $this->paginatorFactory = $factory;
        return $this;
    }

    /**
     * @return int
     */
    protected function getTotalCount()
    {
        $result = $this->readFromConnection($this->getTotalCountQuery())->first();
        return $result ? $result['total'] : 0;
    }

    /**
     * @return Query
     */
    protected function getTotalCountQuery()
    {
        if ($this->countQuery) {
            return $this->countQuery;
        }

        $query = clone $this;
        $query->offset(null)->limit(null);
        $query->columns = [];
        $query->select(new Expression('COUNT(*) as total'));
        $query->orderBys = [];
        return $query;
    }

    /**
     * @param $page
     * @param $perPage
     *
     * @return Result
     */
    protected function runPaginated($page, $perPage)
    {
        $this->offset(($page - 1) * $perPage, $perPage);
        return $this->readFromConnection($this);
    }

    /**
     * @param Query $query
     *
     * @return Result
     */
    protected function readFromConnection(Query $query)
    {
        $expression = $this->getRenderer()->render($query);

        if (!$expression instanceof SQLExpression) {
            return $this->getConnection()->select("$expression");
        }

        return $this->getConnection()->select($expression->toString(), $expression->getBindings());
    }

    /**
     * @param string $operation (INSERT|UPDATE|REPLACE|DELETE)
     * @param bool $returnResult
     * @param array $values (optional)
     *
     * @return int
     */
    protected function writeToConnection($operation, $returnResult, array $values = [])
    {
        $this->operation = $operation;

        if ($values) {
            $this->values($values);
        }

        $expression = $this->getRenderer()->render($this);
        $con = $this->getConnection();

        $method = $operation == 'INSERT' ? 'insert' : 'write';

        if ($expression instanceof SQLExpression) {
            return $con->$method($expression->toString(), $expression->getBindings(), $returnResult);
        }

        return $con->$method("$expression", [], $returnResult);
    }

    /**
     * @return bool
     */
    protected function paginatorClassExists()
    {
        if (!is_bool(static::$paginatorClassExists)) {
            static::$paginatorClassExists = class_exists(Paginator::class);
        }
        return static::$paginatorClassExists;
    }

}

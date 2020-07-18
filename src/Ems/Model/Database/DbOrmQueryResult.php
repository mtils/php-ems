<?php
/**
 *  * Created by mtils on 01.06.20 at 09:57.
 **/

namespace Ems\Model\Database;


use Ems\Contracts\Model\Paginatable;
use Ems\Contracts\Model\Result;
use Ems\Core\Collections\NestedArray;
use Ems\Contracts\Model\Database\Query as QueryContract;
use Ems\Contracts\Model\OrmQuery;
use Ems\Contracts\Model\Database\Connection as ConnectionContract;
use Ems\Model\ResultTrait;
use Ems\Pagination\Paginator;
use Exception;
use Traversable;

use function call_user_func;
use function is_array;

class DbOrmQueryResult implements Result, Paginatable
{
    use ResultTrait;

    private $chunkSize = 1000;

    /**
     * @var ConnectionContract
     */
    private $connection;

    /**
     * @var OrmQuery
     */
    private $ormQuery;

    /**
     * @var QueryContract
     */
    private $dbQuery;

    /**
     * @var QueryContract
     */
    private $toManyQuery;

    /**
     * @var Query
     */
    private $countQuery;

    /**
     * @var callable
     */
    private $countQueryProvider;

    /**
     * @var string|string[]
     */
    private $primaryKey;

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @throws Exception on failure.
     */
    public function getIterator()
    {

        // TODO 11.07.2020 Assign Contracts\Query and DbConnection
        // NEXT STEP IS USING Contracts\Query not Query
        // Assign the connection
        // Get the Renderer by direct instantiation and the dialect from
        // connection (see DbConnection::query())
        // more or less copy Query::readFromConnection() and runPaginated here
        // Then let the DbOrmQueryRunner create all three Queries and assign
        // the connection and Queries to this result
        // Then perhaps let OrmQueryBuilder implement OrmQueryRunner and
        // move all the code into it.
        // It would be nice to NOT create a CountQuery before knowing that someone
        // likes to paginate. So perhaps we have to realize this by a closure


        // Retrieve $chunkSize from "main" query
        if (!$this->dbQuery->limit) {
            $this->dbQuery->limit = $this->chunkSize;
        }
        $buffer = [];
        foreach ($this->dbQuery as $mainRow) {
            $mainId = $this->identify($mainRow);
            $structured = NestedArray::toNested($mainRow, '__');
            $buffer[$mainId] = $structured;
        }

        // HasManyQuery is "the opposite" of main query. It contains all columns
        // of all related objects that are many to main
        // Its result contains the main objects ID and all (cartesian) has many
        // relational rows (or many to many)
        // Basically this just means the main query contains all columns of
        // hasOne/belongsTo relations (and a distinct)
        // The HasMyQuery contains all other columns and whereIn mainId ()
        foreach ($this->toManyQuery as $hasManyRow) {
            $mainId = $this->identify($hasManyRow);
            $buffer[$mainId] = $this->mergeHasManyRow($buffer[$mainId], $hasManyRow);
        }



        /***********************************************************************
         * Deprecated from here
         **********************************************************************/
        // Its better to implement a separate append() method to the top level
        // API. Like:
        // $result = $orm->query(User::class)->where('category.id', 12);
        // $orm->append($result, ['address', 'projects'], $limit = 100)
        foreach ($this->appendingsNotInEager as $relationPath) {
            $ids = [];
            foreach ($this->findIdsOfRelationPath($relationPath) as $id) {
                $ids[] = $id;
            }
            $rows = $this->queryBuilder->getRelated($relationPath, $ids);
            $this->mergeRelated($buffer, $rows);
        }

        // Iterate until end, the same for the next $this->chunkSize
    }

    /**
     * Paginate the result. Return whatever paginator you use.
     * The paginator should be \Traversable.
     *
     * @param int $page (optional)
     * @param int $perPage (optional)
     *
     * @return \Traversable|array A paginator instance or just an array
     **/
    public function paginate($page = 1, $perPage = 15)
    {
        $dbQuery = $this->getDbQuery();
        $paginator = new Paginator($page, $perPage, $this);

        $dbQuery->offset($paginator->getOffset(), $perPage);

        $buffer = [];


        foreach ($dbQuery as $row) {
            $mainId = $this->identify($row);
            $structured = NestedArray::toNested($row, '__');
            $buffer[$mainId] = $structured;
        }


    }

    /**
     * @return ConnectionContract
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param ConnectionContract $connection
     *
     * @return DbOrmQueryResult
     */
    public function setConnection(ConnectionContract $connection)
    {
        $this->connection = $connection;
        return $this;
    }


    /**
     * @return OrmQuery
     */
    public function getOrmQuery()
    {
        return $this->ormQuery;
    }

    /**
     * @param OrmQuery $ormQuery
     *
     * @return DbOrmQueryResult
     */
    public function setOrmQuery(OrmQuery $ormQuery)
    {
        $this->ormQuery = $ormQuery;
        return $this;
    }


    /**
     * @return QueryContract
     */
    public function getDbQuery()
    {
        return $this->dbQuery;
    }

    /**
     * @param QueryContract $dbQuery
     *
     * @return DbOrmQueryResult
     */
    public function setDbQuery(QueryContract $dbQuery)
    {
        $this->dbQuery = $dbQuery;
        return $this;
    }

    /**
     * @return QueryContract
     */
    public function getToManyQuery()
    {
        return $this->toManyQuery;
    }

    /**
     * @param QueryContract $toManyQuery
     *
     * @return DbOrmQueryResult
     */
    public function setToManyQuery(QueryContract $toManyQuery)
    {
        $this->toManyQuery = $toManyQuery;
        return $this;
    }

    /**
     * @return Query
     */
    public function getCountQuery()
    {
        if (!$this->countQuery && $this->countQueryProvider) {
            $this->countQuery = call_user_func($this->countQueryProvider,
                $this->getDbQuery(), $this->getOrmQuery()
            );
        }
        return $this->countQuery;
    }

    /**
     * @param Query $countQuery
     * @return DbOrmQueryResult
     */
    public function setCountQuery(Query $countQuery)
    {
        $this->countQuery = $countQuery;
        return $this;
    }

    /**
     * Get the callable that can create the count query.
     *
     * @return callable
     */
    public function getCountQueryProvider()
    {
        return $this->countQueryProvider;
    }

    /**
     * Assign a callable to defer the creation of a count query.
     *
     * @param callable $countQueryProvider
     *
     * @return $this
     */
    public function provideCountQuery(callable $countQueryProvider)
    {
        $this->countQueryProvider = $countQueryProvider;
        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param string|string[] $primaryKey
     * @return DbOrmQueryResult
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }


    /**
     * @param array $row
     *
     * @return string
     */
    protected function identify(array $row)
    {
        if (is_string($this->primaryKey)) {
            return $row[$this->primaryKey];
        }

        $values = [];

        foreach($this->primaryKey as $keyPart) {
            $values[] = $row[$keyPart];
        }

        return implode('|', $values);
    }
}
<?php
/**
 *  * Created by mtils on 01.06.20 at 09:57.
 **/

namespace Ems\Model\Database;


use ArrayIterator;
use Ems\Contracts\Model\Database\Connection as ConnectionContract;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\Database\Query as QueryContract;
use Ems\Contracts\Model\OrmQuery;
use Ems\Contracts\Model\Paginatable;
use Ems\Contracts\Model\Result;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Core\Collections\NestedArray;
use Ems\Model\ChunkIterator;
use Ems\Model\ResultTrait;
use Ems\Pagination\Paginator;
use Exception;
use Traversable;

use function array_keys;
use function array_values;
use function call_user_func;
use function get_class;
use function is_array;
use function print_r;

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
     * @var Query
     */
    private $countQuery;

    /**
     * @var callable
     */
    private $countQueryProvider;

    /**
     * @var QueryRenderer
     */
    private $renderer;

    /**
     * @var SchemaInspector
     */
    private $inspector;

    /**
     * @var array
     */
    private $primaryKeyCache = [];

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @throws Exception on failure.
     */
    public function getIterator()
    {
        if (!$this->chunkSize || $this->dbQuery->limit) {
            return new ArrayIterator($this->run());
        }

        $handler = function ($offset, $limit) {
            return $this->run($offset, $limit);
        };

        return new ChunkIterator($handler, $this->chunkSize);

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
        $primaryKey = $this->inspector->primaryKey($this->ormQuery->ormClass);

        $buffer = [];


        foreach ($dbQuery as $row) {
            $mainId = $this->identify($row, $primaryKey);
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
     * @return SchemaInspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }

    /**
     * @param SchemaInspector $inspector
     * @return DbOrmQueryResult
     */
    public function setInspector(SchemaInspector $inspector)
    {
        $this->inspector = $inspector;
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
     * @return int
     */
    public function getChunkSize()
    {
        return $this->chunkSize;
    }

    /**
     * @param int $chunkSize
     * @return DbOrmQueryResult
     */
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;
        return $this;
    }


    protected function run($offset=null, $chunkSize=null)
    {
        $oldOffset = $this->dbQuery->offset;
        $oldLimit = $this->dbQuery->limit;

        if ($offset !== null) {
            $this->dbQuery->offset($offset, $chunkSize);
        }

        $expression = $this->renderer()->renderSelect($this->dbQuery);
        $this->dbQuery->offset($oldOffset);
        $this->dbQuery->limit($oldLimit);

        $dbResult = $this->connection->select($expression->toString(), $expression->getBindings());
        $primaryKey = $this->inspector->primaryKey($this->ormQuery->ormClass);
        $buffer = [];

        foreach ($dbResult as $mainRow) {
            $mainId = $this->identify($mainRow, $primaryKey);
            $buffer[$mainId] = $this->toNested($mainRow);
        }

        if (!$toManyQuery = $this->dbQuery->getAttached(OrmQueryBuilder::TO_MANY)) {
            return array_values($buffer);
        }

        // Restrict to the result ids
        $mainTable = $this->inspector->getStorageName($this->ormQuery->ormClass);
        $sqlPrimaryKey = "$mainTable.$primaryKey";
        $toManyQuery->where($sqlPrimaryKey, 'in', array_keys($buffer));

        $toManyExpression = $this->renderer()->renderSelect($toManyQuery);
        $toManyResult = $this->connection->select($toManyExpression->toString(), $toManyExpression->getBindings());

        foreach ($toManyResult as $hasManyRow) {
            $nested = $this->toNested($hasManyRow);
            $mainId = $this->identify($hasManyRow, $primaryKey);
            $buffer[$mainId] = $this->mergeToManyRow($buffer[$mainId], $nested, $primaryKey);
        }

        return array_values($buffer);
    }

    protected function primaryKey($path)
    {
        if (isset($this->primaryKeyCache[$path])) {
            return $this->primaryKeyCache[$path];
        }
        $relation = $this->inspector->getRelationship($this->ormQuery->ormClass, $path);
        $this->primaryKeyCache[$path] = $this->inspector->primaryKey(get_class($relation->related));
        return $this->primaryKeyCache[$path];
    }

    protected function mergeToManyRow(array $mainRow, array $toManyRow, $primaryKey)
    {

        foreach((array)$primaryKey as $key) {
            unset($toManyRow[$key]);
        }
        foreach ($toManyRow as $key=>$value) {

            if (!isset($mainRow[$key])) {
                $mainRow[$key] = [];
            }
            $relatedKey = $this->primaryKey($key);
            $relatedId = $this->identify($value, $relatedKey);
            foreach ($mainRow[$key] as $alreadyAdded) {
                if($this->identify($alreadyAdded, $relatedKey) == $relatedId) {
                    continue 2;
                }
            }
            $mainRow[$key][] = $value;
        }
        return $mainRow;
    }

    /**
     * @param array        $row
     * @param string|array $primaryKey
     *
     * @return string
     */
    protected function identify(array $row, $primaryKey)
    {
        if (is_string($primaryKey)) {
            return $row[$primaryKey];
        }

        $values = [];

        foreach($primaryKey as $keyPart) {
            $values[] = $row[$keyPart];
        }

        return implode('|', $values);
    }

    protected function toNested($row)
    {
        $structured = NestedArray::toNested($row, '__');
        $this->removeEmptyRelations($structured);
        return $structured;
    }

    protected function removeEmptyRelations(array &$row)
    {
        // No indexed or empty arrays
        if (isset($row[0]) || !$row) {
            return;
        }

        $allEmpty = true;
        foreach ($row as $key=>$value) {
            if (is_array($value)) {
                $this->removeEmptyRelations($row[$key]);
            }
            if ($row[$key] !== null && $row[$key] !== '' && $row[$key] !== []) {
                $allEmpty = false;
            }
        }
        if ($allEmpty) {
            $row = [];
        }
    }

    /**
     * @return QueryRenderer
     */
    protected function createRenderer()
    {
        $renderer = new QueryRenderer();
        $dialect = $this->connection->dialect();
        $dialect = $dialect instanceof Dialect ? $dialect : SQL::dialect($dialect);
        $renderer->setDialect($dialect);
        return $renderer;
    }

    /**
     * @return QueryRenderer
     */
    private function renderer()
    {
        if (!$this->renderer) {
            $this->renderer = $this->createRenderer();
        }
        return $this->renderer;
    }
}
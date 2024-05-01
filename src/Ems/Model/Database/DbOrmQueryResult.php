<?php
/**
 *  * Created by mtils on 01.06.20 at 09:57.
 **/

namespace Ems\Model\Database;


use ArrayIterator;
use Closure;
use DateTime;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Model\Database\Connection as ConnectionContract;
use Ems\Contracts\Model\Database\Query as QueryContract;
use Ems\Contracts\Model\OrmQuery;
use Ems\Contracts\Model\Paginatable;
use Ems\Contracts\Model\Result;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Core\Collections\NestedArray;
use Ems\Core\Patterns\HookableTrait;
use Ems\Model\ChunkIterator;
use Ems\Model\ResultTrait;
use Ems\Pagination\Paginator;
use Exception;
use Traversable;

use function array_keys;
use function array_pop;
use function array_values;
use function call_user_func;
use function explode;
use function get_class;
use function is_array;


class DbOrmQueryResult implements Result, Paginatable, HasMethodHooks
{
    use ResultTrait;
    use HookableTrait;

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
     * @var
     */
    private $typeProvider;

    /**
     * @var RelationMap
     */
    private $map;

    /**
     * @var array
     */
    private $primaryKeyCache = [];

    /**
     * @var array
     */
    private $typeCache = [];

    /**
     * @var string
     */
    private $dateFormat = '';

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @throws Exception on failure.
     */
    #[\ReturnTypeWillChange]
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
     * @return Paginator A paginator instance or just an array
     **/
    public function paginate($page = 1, $perPage = 15)
    {
        $paginator = new Paginator($page, $perPage, $this);
        $paginator->setResult($this->run($paginator->getOffset(), $perPage), $this->makeCountRunner());
        return $paginator;
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
     * @return RelationMap
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * @param RelationMap $map
     * @return DbOrmQueryResult
     */
    public function setMap(RelationMap $map)
    {
        $this->map = $map;
        return $this;
    }

    /**
     * @return QueryRenderer
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * @param QueryRenderer $renderer
     * @return DbOrmQueryResult
     */
    public function setRenderer(QueryRenderer $renderer)
    {
        $this->renderer = $renderer;
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

    /**
     * {@inheritDoc}
     *
     * @return array
     **/
    public function methodHooks()
    {
        return ['run'];
    }


    /**
     * Run the query and format its result
     *
     * @param int|null $offset
     * @param int|null $chunkSize
     * @return array
     */
    protected function run(int $offset=null, int $chunkSize=null) : array
    {
        $oldOffset = $this->dbQuery->offset;
        $oldLimit = $this->dbQuery->limit;

        // Clear date format cache because we are not sure to have the same
        // connection
        $this->dateFormat = '';

        if ($offset !== null) {
            $this->dbQuery->offset($offset, $chunkSize);
        }

        $this->callBeforeListeners('run', [$this->ormQuery, $this->dbQuery]);
        $expression = $this->renderer->renderSelect($this->dbQuery);
        $this->dbQuery->offset($oldOffset);
        $this->dbQuery->limit($oldLimit);

        $dbResult = $this->connection->select($expression->toString(), $expression->getBindings());

        $primaryKey = $this->inspector->primaryKey($this->ormQuery->ormClass);
        $buffer = [];

        foreach ($dbResult as $mainRow) {
            $mainId = $this->identify($mainRow, $primaryKey);
            $buffer[$mainId] = $this->format($this->ormQuery->ormClass, $mainRow);
        }

        if (!$toManyQuery = $this->dbQuery->getAttached(OrmQueryBuilder::TO_MANY)) {
            $rows = array_values($buffer);
            $this->callAfterListeners('run', [$this->ormQuery, $this->dbQuery, $rows]);
            return $rows;
        }

        // Restrict to the result ids
        $mainTable = $this->inspector->getStorageName($this->ormQuery->ormClass);
        $sqlPrimaryKey = "$mainTable.$primaryKey";
        $toManyQuery->where($sqlPrimaryKey, 'in', array_keys($buffer));

        $toManyExpression = $this->renderer->renderSelect($toManyQuery);
        $toManyResult = $this->connection->select($toManyExpression->toString(), $toManyExpression->getBindings());

        foreach ($toManyResult as $hasManyRow) {

            $nested = $this->toNested($hasManyRow);
            $mainId = $this->identify($hasManyRow, $primaryKey);
            $this->removePrimaryKey($nested, $primaryKey);
            $this->mergeToManyRow($buffer[$mainId], $nested);
        }

        $rows = array_values($buffer);
        $this->callAfterListeners('run', [$this->ormQuery, $this->dbQuery, $rows]);
        return $rows;
    }

    /**
     * Create a closure that will create the count query.
     *
     * @return Closure
     */
    protected function makeCountRunner() : Closure
    {
        return function () {
            $query = $this->getCountQuery();
            $expression = $this->renderer->renderSelect($query);
            $dbResult = $this->connection->select($expression->toString(), $expression->getBindings());
            return (int)$dbResult->first()['total_count'];
        };
    }

    /**
     * Get the primary key of of $path relative to root class.
     *
     * @param string $path
     * @return string|string[]
     */
    protected function primaryKey(string $path)
    {
        if (isset($this->primaryKeyCache[$path])) {
            return $this->primaryKeyCache[$path];
        }
        $parentClass = $this->ormQuery->ormClass;
        $stack = [];
        foreach(explode('.', $path) as $segment) {
            $stack[] = $segment;
            $currentPath = implode('.', $stack);
            $relation = $this->inspector->getRelationship($parentClass, $segment);
            $parentClass = get_class($relation->related);
            $this->primaryKeyCache[$currentPath] = $this->inspector->primaryKey($parentClass);
        }

        return $this->primaryKeyCache[$path];
    }

    /**
     * Get the type for $key of $class and cache it.
     *
     * @param string $class
     * @param string $key
     * @return string
     */
    protected function getType(string $class, string $key) : string
    {
        $cacheId = "$class|$key";
        if (isset($this->typeCache[$cacheId])) {
            return $this->typeCache[$cacheId];
        }
        if (!$type = $this->inspector->getType($class, $key)) {
            $type = '';
        }

        $this->typeCache[$cacheId] = $type;
        return $this->typeCache[$cacheId];
    }

    /**
     * @param array $toManyRow
     * @param $primaryKey
     */
    protected function removePrimaryKey(array &$toManyRow, $primaryKey)
    {
        foreach((array)$primaryKey as $key) {
            unset($toManyRow[$key]);
        }
    }

    /**
     * Merge the to-many-result into the top level result row
     *
     * @param array $mainRow
     * @param array $toManyRow
     * @param array $pathStack
     */
    protected function mergeToManyRow(array &$mainRow, array $toManyRow, array $pathStack=[])
    {

        foreach ($toManyRow as $key=>$objectData) {

            if (!is_array($objectData)) {
                $mainRow[$key] = $objectData;
                continue;
            }
            $pathStack[] = $key;
            $currentPath = implode('.', $pathStack);

            $relation = $this->map->relation($currentPath);

            if (!isset($mainRow[$key])) {
                $mainRow[$key] = [];
            }

            // If it is an empty array we already added an empty array
            if (!$objectData) {
                array_pop($pathStack);
                continue;
            }

            // From here it should be always data for one orm object
            $relatedKey = $this->primaryKey($currentPath);
            $hasMany = $relation->hasMany;

            if ($relatedClass = get_class($relation->related)) {
                $this->cast($relatedClass, $objectData);
            }

            if ($hasMany) {
                $relatedId = $this->identify($objectData, $relatedKey);
                if (!isset($mainRow[$key][$relatedId])) {
                    $mainRow[$key][$relatedId] = [];
                }
                $node = &$mainRow[$key][$relatedId];
            } else {
                $node = &$mainRow[$key];
            }

            // Then check each of the keys if one is a relation
            foreach ($objectData as $subKey=>$subValue) {
                if (!is_array($subValue)) {
                    $node[$subKey] = $subValue;
                    continue;
                }
                $this->mergeToManyRow($node, [$subKey => $subValue], $pathStack);
            }

            array_pop($pathStack);
        }
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

    /**
     * Format each row (make it multidimensional and cast its values)
     *
     * @param string $ormClass
     * @param array $row
     * @return array
     */
    protected function format(string $ormClass, array $row) : array
    {
        $nested = $this->toNested($row);
        $this->cast($ormClass, $nested);
        return $nested;
    }

    /**
     * Convert the __ separated flat result into a nested array.
     *
     * @param array $row
     * @return array
     */
    protected function toNested(array $row) : array
    {
        $structured = NestedArray::toNested($row, '__');
        $this->removeEmptyRelations($structured);
        return $structured;
    }

    /**
     * Cast the values
     * @param string $class
     * @param array $row
     */
    protected function cast(string $class, array &$row)
    {
        $dateFormat = $this->getDateFormat();
        foreach ($row as $key=>$value) {
            if (!$type = $this->getType($class, $key)) {
                continue;
            }

            if ($type == DateTime::class && !$value instanceof DateTime) {
                $row[$key] = DateTime::createFromFormat($dateFormat, $value);
                continue;
            }
            if ($type == 'int' || $type == 'integer') {
                $row[$key] = (int)$value;
                continue;
            }
            if ($type == 'float' || $type == 'double') {
                $row[$key] = (float)$value;
                continue;
            }
            if ($type == 'bool' || $type == 'boolean') {
                $row[$key] = (bool)$value;
                continue;
            }
            if (!is_array($value)) {
                continue;
            }

            if (!$relation = $this->inspector->getRelationship($class, $key)) {
                continue;
            }
            $this->cast(get_class($relation->related), $row[$key]);

        }
    }

    /**
     * @return string
     */
    protected function getDateFormat() : string
    {
        if (!$this->dateFormat) {
            $this->dateFormat = $this->connection->dialect()->timeStampFormat();
        }
        return $this->dateFormat;
    }

    /**
     * Clear the relations that were not loaded from the database.
     *
     * @param array $row
     */
    protected function removeEmptyRelations(array &$row)
    {
        // Remove indexed or empty arrays
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

}
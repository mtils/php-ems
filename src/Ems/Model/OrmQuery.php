<?php

/**
 *  * Created by mtils on 13.04.20 at 06:55.
 **/

namespace Ems\Model;

use ArrayAccess;
use Ems\Contracts\Core\Connection;
use Ems\Contracts\Core\ObjectArrayConverter;
use Ems\Contracts\Model\OrmQuery as BaseOrmQuery;
use Ems\Contracts\Model\OrmQueryRunner;
use Ems\Contracts\Model\Paginatable;
use Ems\Contracts\Model\Result;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Pagination\Paginator;
use Exception;
use Iterator;
use RuntimeException;
use Traversable;

use function get_class;

class OrmQuery extends BaseOrmQuery implements Result, Paginatable
{
    use ResultTrait;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var OrmQueryRunner
     */
    private $runner;

    /**
     * @var ObjectArrayConverter
     */
    private $objectFactory;

    /**
     * @var SchemaInspector
     */
    private $inspector;

    /**
     * {@inheritDoc}
     *
     * @throws Exception on failure.
     */
    public function getIterator()
    {
        foreach ($this->createResult() as $item) {
            yield $this->toObject($item);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param int $page (optional)
     * @param int $perPage (optional)
     *
     * @return Traversable|array A paginator instance or just an array
     **/
    public function paginate($page = 1, $perPage = 15)
    {
        $result = $this->createResult();
        $paginatable = $this->toPaginatable($result);

        $paginated = $paginatable->paginate($page, $perPage);

        $objects = [];
        foreach ($paginated as $data) {
            $objects[] = $this->toObject($data);
        }

        $paginator = new Paginator($page, $perPage);

        $totalCountProvider = null;

        if ($paginated instanceof Paginator && $paginated->hasTotalCount()) {
            $totalCountProvider = function () use ($paginated) {
                return $paginated->getTotalCount();
            };
        }

        $paginator->setResult($objects, $totalCountProvider);

        return $paginator;

    }

    /**
     * Create an orm object.
     *
     * @param array $values
     * @return object
     */
    public function create(array $values)
    {
        $ormClass = $this->ormClass;
        foreach($this->inspector->getDefaults($ormClass) as $key=>$value) {
            if (!isset($values[$key])) {
                $values[$key] = $value;
            }
        }

        $lastInsertedId = $this->getRunner()
                ->create($this->getConnection(), $ormClass, $values);

        $primaryKey = $this->inspector->primaryKey($ormClass);

        return $this->where($primaryKey, $lastInsertedId)->first();
    }

    /**
     * Save $ormObject to storage. Return all values sent to the database if
     * it was saved. This allows to set updated timestamps etc. to the object.
     * Currently it is not possible to assign the values to the passed object.
     *
     * @param object $ormObject
     *
     * @return array Return all values sent to the database. (empty if none)
     */
    public function save($ormObject)
    {
        $this->conditions->clear();

        $ormClass = get_class($ormObject);
        $values = $this->objectFactory->toArray($ormObject, 1);
        foreach($this->inspector->getAutoUpdates($ormClass) as $key=>$value) {
            $values[$key] = $value;
        }
        $primaryKey = $this->inspector->primaryKey($ormClass);
        $this->where($primaryKey, $values[$primaryKey]);

        $updated = $this->getRunner()->update($this->getConnection(), $this, $values);
        if ($updated == 1) {
            return $values;
        }
        if ($updated > 1) {
            throw new RuntimeException("More than one rows were updated when saving $ormClass #". $values[$primaryKey]);
        }
        return [];
    }

    /**
     * Update all rows matching the current criteria.
     *
     * @param array $values
     * @return int THe amount of changes rows.
     */
    public function update(array $values) : int
    {
        $this->conditions->clear();
        $ormClass = $this->ormClass;

        // Also write auto update values
        foreach($this->inspector->getAutoUpdates($ormClass) as $key=>$value) {
            $values[$key] = $value;
        }

        return $this->getRunner()->update($this->getConnection(), $this, $values);
    }

    /**
     * Delete the passed object or by current where conditions.
     *
     * @param object|null $ormObject
     * @return int
     */
    public function delete($ormObject=null) : int
    {
        if (!$ormObject) {
            return $this->getRunner()->delete($this->getConnection(), $this);
        }
        $this->conditions->clear();
        $ormClass = get_class($ormObject);
        $primaryKey = $this->inspector->primaryKey($ormClass);
        $values = $this->objectFactory->toArray($ormObject, 1);
        $this->where($primaryKey, $values[$primaryKey]);
        return $this->getRunner()->delete($this->getConnection(), $this);
    }

    /**
     * @return OrmQueryRunner
     */
    public function getRunner()
    {
        return $this->runner;
    }

    /**
     * @param OrmQueryRunner $runner
     * @return OrmQuery
     */
    public function setRunner(OrmQueryRunner $runner)
    {
        $this->runner = $runner;
        return $this;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param Connection $connection
     * @return OrmQuery
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @return ObjectArrayConverter
     */
    public function getObjectFactory()
    {
        return $this->objectFactory;
    }

    /**
     * @param ObjectArrayConverter $objectFactory
     * @return self
     */
    public function setObjectFactory(ObjectArrayConverter $objectFactory)
    {
        $this->objectFactory = $objectFactory;
        return $this;
    }

    /**
     * @return SchemaInspector|null
     */
    public function getSchemaInspector() : ?SchemaInspector
    {
        return $this->inspector;
    }

    /**
     * @param SchemaInspector|null $inspector
     * @return $this
     */
    public function setSchemaInspector(SchemaInspector $inspector=null) : OrmQuery
    {
        $this->inspector = $inspector;
        return $this;
    }

    /**
     * @return Result|Paginatable|Iterator
     */
    protected function createResult()
    {
        if (!$this->connection || !$this->runner) {
            return new GenericResult([]);
        }
        return $this->runner->retrieve($this->connection, $this);
    }

    /**
     * @param Result $result
     *
     * @return Paginatable
     */
    protected function toPaginatable(Result $result)
    {

        if ($result instanceof Paginatable) {
            return $result;
        }

        return new GenericPaginatableResult($result, function ($page, $perPage) use ($result) {
            $paginator = new Paginator($page, $perPage);
            return $paginator->slice($result);
        });

    }

    /**
     * @param array|ArrayAccess $row
     *
     * @return object
     */
    protected function toObject($row)
    {
        return $this->objectFactory->fromArray($this->ormClass, $row, true);
    }
}
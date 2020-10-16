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
use Ems\Pagination\Paginator;
use Exception;
use Iterator;
use Traversable;

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
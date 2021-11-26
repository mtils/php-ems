<?php
/**
 *  * Created by mtils on 13.04.20 at 06:53.
 **/

namespace Ems\Model;

use Ems\Contracts\Core\ConnectionPool;
use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Core\ObjectArrayConverter;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Model\OrmQueryRunner;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Patterns\ExtendableTrait;

class Orm implements Extendable
{
    use ExtendableTrait;

    /**
     * @var ConnectionPool
     */
    private $connections;

    /**
     * @var SchemaInspector
     */
    private $inspector;

    /**
     * @var ObjectArrayConverter
     */
    private $objectFactory;

    /**
     * @var array
     */
    private $runnerCache = [];

    public function __construct(ConnectionPool $connections, SchemaInspector $inspector, ObjectArrayConverter $objectFactory)
    {
        $this->connections = $connections;
        $this->inspector = $inspector;
        $this->objectFactory = $objectFactory;
    }

    /**
     * @param string $class
     *
     * @return OrmQuery
     */
    public function query(string $class)
    {
        $query = new OrmQuery($class);

        $url = $this->inspector->getStorageUrl($class);

        $connection = $this->connections->connection($url);

        $query->setRunner($this->runner($url))
            ->setConnection($connection)
            ->setObjectFactory($this->objectFactory)
            ->setSchemaInspector($this->inspector);

        return $query;

    }

    /**
     * @param Url $url
     *
     * @return OrmQueryRunner
     */
    public function runner(Url $url)
    {
        $cacheId = (string)$url;
        if (isset($this->runnerCache[$cacheId])) {
            return $this->runnerCache[$cacheId];
        }
        if (!$runner = $this->callUntilNotNull([$url])) {
            throw new HandlerNotFoundException("No handler found to OrmQueryRunner for url '$url'");
        }

        $this->runnerCache[$cacheId] = $runner;
        return $this->runnerCache[$cacheId];
    }
}
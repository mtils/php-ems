<?php
/**
 *  * Created by mtils on 05.11.2021 at 09:59.
 **/

namespace Ems\Model\Eloquent;

use Ems\Contracts\Core\ConnectionPool;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Model\Database\DB;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Connectors\ConnectionFactory;

/**
 * Create a separate illuminate connection for a configured ems connection
 */
class EmsConnectionFactory implements ConnectionResolverInterface
{
    /**
     * @var string
     */
    protected $defaultConnectionName = 'default';

    /**
     * @var Connection[]
     */
    protected $customConnections = [];

    /**
     * @var Connection[]
     */
    protected $resolvedConnections = [];

    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * @var ConnectionFactory
     */
    private $nativeFactory;

    public function __construct(ConnectionPool $connectionPool=null)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * {@inheritDoc}
     *
     * @param string|null $name
     *
     * @return Connection
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->defaultConnectionName;

        if (isset($this->customConnections[$name])) {
            return $this->customConnections[$name];
        }
        if (!isset($this->resolvedConnections[$name])) {
            $this->resolvedConnections[$name] = $this->createFromPool($name);
        }
        return $this->resolvedConnections[$name];
    }

    /**
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->defaultConnectionName;
    }

    /**
     * @param string $name
     */
    public function setDefaultConnection($name)
    {
        $this->defaultConnectionName = $name;
    }

    /**
     * Get a custom connection for $name or null if none was set.
     *
     * @param string $name
     * @return Connection|null
     */
    public function getCustomConnection(string $name) : ?Connection
    {
        return $this->customConnections[$name] ?? null;
    }

    /**
     * Set a custom connection for $name.
     *
     * @param string $name
     * @param Connection|null $connection
     */
    public function setCustomConnection(string $name, ?Connection $connection)
    {
        $this->customConnections[$name] = $connection;
    }

    /**
     * Convert an ems database configuration into laravel.
     *
     * @param array $emsConfig
     *
     * @return array
     */
    public static function configToLaravelConfig(array $emsConfig) : array
    {
        $map = [
            'user' => 'username'
        ];
        $laravelConfig = [];
        foreach ($emsConfig as $key=>$value) {
            $laravelConfig[$map[$key] ?? $key] = $value;
        }
        return $laravelConfig;
    }

    /**
     * @return ConnectionPool
     */
    public function getConnectionPool(): ?ConnectionPool
    {
        return $this->connectionPool;
    }

    /**
     * @param ConnectionPool|null $connectionPool
     */
    public function setConnectionPool(?ConnectionPool $connectionPool): void
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * Create illuminate connection from an ems' connection from ConnectionPool.
     *
     * @param string $name
     * @return Connection
     *
     * @throws UnConfiguredException
     */
    protected function createFromPool(string $name) : Connection
    {
        if (!$this->connectionPool) {
            throw new UnConfiguredException("You have to assign a ConnectionPool to create connections from it");
        }
        $emsConnection = $this->connectionPool->connection($name);
        $config = static::configToLaravelConfig(DB::urlToConfig($emsConnection->url()));
        return $this->getNativeFactory()->make($config, $name);
    }

    /**
     * @return ConnectionFactory
     */
    protected function getNativeFactory() : ConnectionFactory
    {
        if (!$this->nativeFactory) {
            $this->nativeFactory = new ConnectionFactory(new Container());
        }
        return $this->nativeFactory;
    }
}
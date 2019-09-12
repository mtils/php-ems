<?php
/**
 *  * Created by mtils on 12.09.19 at 08:10.
 **/

namespace Ems\Concurrency;


use Ems\Contracts\Concurrency\Exceptions\AcquireException;
use Ems\Contracts\Concurrency\Exceptions\ReleaseException;
use Ems\Contracts\Concurrency\Handle;
use Ems\Contracts\Concurrency\Manager;
use Ems\Contracts\Core\Map;
use Ems\Contracts\Expression\Prepared;
use Ems\Contracts\Model\Database\Connection;
use Ems\Core\Exceptions\KeyLengthException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Exception;
use function strtolower;
use function var_dump;

class MySQLLockManager extends AbstractManager implements Manager
{
    const ENDLESS_TIMEOUT = -1;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $prefix = '';

    /**
     * @var Prepared
     */
    private $lockStatement;

    /**
     * @var Prepared
     */
    private $releaseStatement;

    public function __construct(Connection $connection=null, array $attributes=[])
    {
        if ($connection) {
            $this->setConnection($connection);
        }
        if (isset($attributes['tries'])) {
            $this->tries = $attributes['tries'];
        }
        if (isset($attributes['retryDelay'])) {
            $this->retryDelay = $attributes['retryDelay'];
        }
        if (isset($attributes['prefix'])) {
            $this->prefix = $attributes['prefix'];
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param string $uri
     * @param int $ttlMilliseconds (optional)
     *
     * @return Handle|null
     *
     * @throws \Exception
     */
    public function lock($uri, $ttlMilliseconds = null)
    {
        $seconds = !$ttlMilliseconds ? static::ENDLESS_TIMEOUT : $ttlMilliseconds/1000;
        $uriKey = $this->uriKey($uri);

        $result = $this->loop(function () use ($uriKey, $seconds) {
            return $this->lockOrFail($uriKey, $seconds);
        });

        if ($result) {
            return $this->createHandle($uri, $uriKey, $ttlMilliseconds);
        }

        return null;

    }

    /**
     * Release the handle you got from self::lock()
     *
     * @param Handle $handle
     *
     * @return void
     *
     * @throws Exception
     */
    public function release(Handle $handle)
    {

        $result = $this->releaseStatement()->bind([$handle->token]);
        $result = Map::firstItemValue($result);

        if ($result === NULL) {
            throw new ReleaseException("A lock '$handle->token' did not exist");
        }

        if (!$result) {
            throw new ReleaseException("The lock '$handle->token' seems to was created outside of this thread");
        }

        $this->failIfTtlExceeded($handle);

    }

    /**
     * @return Connection|null
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param Connection $connection
     *
     * @return MySQLLockManager
     */
    public function setConnection(Connection $connection)
    {
        if (strtolower($connection->dialect()) != 'mysql') {
            throw new UnsupportedParameterException('MySQLLockManager only works with mysql');
        }
        $this->connection = $connection;
        $this->lockStatement = null;
        $this->releaseStatement = null;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        if (!$this->prefix) {
            return $this->connection->url()->path->first() . '.'; // Database name
        }
        return $this->prefix;
    }

    /**
     * @param string $prefix
     * @return MySQLLockManager
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $uri
     * @param int    $ttlMilliseconds (optional)
     *
     * @return bool
     */
    public function lockOrFail($uriKey, $seconds)
    {
        $result = $this->lockStatement()->bind([$uriKey, $seconds]);
        $result = Map::firstItemValue($result);

        if ($result !== null) {
            return (bool)$result;
        }

        if ($seconds === static::ENDLESS_TIMEOUT) {
            throw new AcquireException('MySQL didnt create the lock. Perhaps try it with a timeout. (no endless lock)');
        }

        throw new AcquireException('MySQL didnt create the lock.');
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    protected function uriKey($uri)
    {
        $maxLength = 64;
        $key = $this->getPrefix() . $uri;
        if (strlen($key) > $maxLength) {
            throw new KeyLengthException("MySQL lock names only $maxLength chars. '$key' is too long.");
        }
        return $key;
    }

    /**
     * @return Prepared
     */
    protected function lockStatement()
    {
        if (!$this->lockStatement) {
            $this->lockStatement = $this->connection->prepare('SELECT GET_LOCK(?,?)');
        }
        return $this->lockStatement;
    }

    /**
     * @return Prepared
     */
    protected function releaseStatement()
    {
        if (!$this->releaseStatement) {
            $this->releaseStatement = $this->connection->prepare('SELECT RELEASE_LOCK(?)');
        }
        return $this->releaseStatement;
    }

    /**
     * Create a new instance of your class with retry parameter assigned.
     * @param int $tries
     * @param int $delay
     * @param array $attributes (optional)
     * @return static
     */
    protected function replicate($tries, $delay, array $attributes = [])
    {

        $attributes['prefix'] = $this->getPrefix();
        $attributes['tries'] = $tries;
        $attributes['retryDelay'] = $delay;
        return new static($this->connection, $attributes);
    }


}
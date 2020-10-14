<?php
/**
 *  * Created by mtils on 24.05.19 at 08:09.
 **/

namespace Ems\Model\Eloquent;


use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Ems\Core\Url;
use Ems\Model\Database\PDOConnection;
use Ems\Model\Database\SQL;
use Illuminate\Database\Connection as IlluminateConnectionClass;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use PDO;
use UnexpectedValueException;
use function call_user_func;
use function in_array;

/**
 * Class IlluminateConnection
 *
 * This is basically a proxy to pass the pdo connection of laravel through
 *
 * @package Ems\Model\Eloquent
 */
class IlluminateConnection extends PDOConnection
{
    /**
     * @var ConnectionInterface
     */
    protected $con;

    /**
     * @var callable
     */
    protected $connectionProvider;

    /**
     * @var callable
     */
    protected $dialectProvider;

    /**
     * IlluminateConnection constructor.
     *
     * @param callable|null $connectionProvider
     */
    public function __construct(callable $connectionProvider=null)
    {
        parent::__construct(new Url());
        if ($connectionProvider) {
            $this->provideConnectionBy($connectionProvider);
        }
        $this->provideDialectBy(function ($dialect) {
            return SQL::dialect($dialect);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @return UrlContract
     **/
    public function url()
    {
        $config = $this->con()->getConfig();

        if (isset($config['url']) && $config['url']) {
            return new Url($config['url']);
        }

        $driver = isset($config['driver']) ? $config['driver'] : 'laravel';
        $host = isset($config['host']) ? $config['host'] : 'localhost';
        $database = isset($config['database']) ? trim($config['database'],':') : 'database';

        $url = (new Url())->scheme($driver)->host($host)->path($database);

        if (isset($config['port'])) {
            $url = $url->port($config['port']);
        }

        if (isset($config['username'])) {
            $url = $url->user($config['username']);
        }

        if (isset($config['password'])) {
            $url = $url->password($config['password']);
        }

        $query = [];

        foreach ($config as $key=>$value) {
            if (!in_array($key, ['driver', 'host', 'port', 'user', 'password', 'database'])) {
                $query[$key] = $value;
            }
        }

        return $query ? $url->query($query) : $url;

    }


    /**
     * {@inheritdoc}
     *
     * @return string (or object with __toString()
     **/
    public function dialect()
    {

        $con = $this->con();

        if($con instanceof MySqlConnection) {
            return $this->makeDialect(SQL::MY);
        }
        if ($con instanceof PostgresConnection) {
            return $this->makeDialect(SQL::POSTGRES);
        }
        if($con instanceof SQLiteConnection) {
            return $this->makeDialect(SQL::SQLITE);
        }
        if($con instanceof SqlServerConnection) {
            return $this->makeDialect(SQL::MS);
        }

        throw new NotImplementedException(Type::of($con) . ' has no matching EMS dialect (is not supported)');
    }

    /**
     * {@inheritDoc}
     *
     * @param string|object $dialect (string or object with __toString)
     *
     * @return self
     **/
    public function setDialect($dialect)
    {
        throw new UnsupportedUsageException("You cannot manually set the dialect of an auto configured laravel connection.");
    }


    /**
     * Assign a callable to provide the illuminate
     * database connection.
     *
     * @param callable $provider
     *
     * @return $this
     */
    public function provideConnectionBy(callable $provider)
    {
        $this->connectionProvider = $provider;
        return $this;
    }

    /**
     * Assign a callable to provide the EMS Dialect object by the dialect name.
     *
     * @param callable $provider
     *
     * @return $this
     */
    public function provideDialectBy(callable $provider)
    {
        $this->dialectProvider = $provider;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param mixed $value
     *
     * @return self
     **@throws \Ems\Contracts\Core\Errors\Unsupported
     *
     */
    public function setOption($key, $value)
    {
        if (!$this->isClassOption($value)) {
            throw new UnsupportedUsageException('Set your options in your laravel connection');
        }
        parent::setOption($key, $value);
        return $this;
    }


    /**
     * @return IlluminateConnectionClass
     */
    protected function con()
    {
        if (!$this->connectionProvider) {
            throw new UnConfiguredException('Assign a connection provider (callable)');
        }

        $con = call_user_func($this->connectionProvider);

        if (!$con instanceof IlluminateConnectionClass) {
            throw new UnexpectedValueException('The connection provider has to return an' . IlluminateConnectionClass::class);
        }
        return $con;
    }

    /**
     * Create a pdo connection NOT by the passed URL.
     *
     * @param UrlContract $url
     * @param bool        $forceNew
     *
     * @return PDO
     */
    protected function createPDO(UrlContract $url, $forceNew=false)
    {
        $con = $this->con();
        if ($forceNew) {
            $con->reconnect();
        }
        return $con->getPdo();
    }

    /**
     * @param callable $attempt
     *
     * @return callable|object
     */
    protected function makeRetry_(callable $attempt)
    {
        $retry = parent::makeRetry($attempt);
        $retry->reConnector = function () {
            $this->con()->reconnect();
        };
        return $retry;
    }


    /**
     * @param string|Stringable $dialect
     *
     * @return Dialect
     */
    protected function makeDialect($dialect)
    {
        return call_user_func($this->dialectProvider, $dialect);
    }
}
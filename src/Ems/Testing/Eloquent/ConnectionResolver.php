<?php


namespace Ems\Testing\Eloquent;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Connection;

/**
 * This is a connection resolver which connects only to one connection
 * It is used for tests to inject one connection
 *
 * @deprecated Use \Ems\Model\Eloquent\EmsConnectionFactory
 **/
class ConnectionResolver implements ConnectionResolverInterface
{

    /**
     * @var array
     **/
    protected $connections = [];

    /**
     * @var string
     **/
    protected $defaultConnectionName = 'tests';


    /**
     * @param Connection $connection (optional)
     **/
    public function __construct($connection=null)
    {
        if ($connection) {
            $this->setConnection($connection);
        }
    }

    /**
    * Get a database connection instance.
    *
    * @param  string  $name
    *
    * @return Connection
    */
    public function connection($name = null)
    {
        return $this->connections[$name ?: $this->defaultConnectionName];
    }

    /**
    * Get a database connection instance.
    *
    * @param ConnectionInterface $connection
    * @param string|null         $name (optional)
    *
    * @return self
    */
    public function setConnection(ConnectionInterface $connection, $name=null)
    {
        $this->connections[$name ?: $this->defaultConnectionName] = $connection;
        return $this;
    }

    /**
    * Get the default connection name.
    *
    * @return string
    */
    public function getDefaultConnection()
    {
        return $this->defaultConnectionName;
    }

    /**
    * Set the default connection name.
    *
    * @param  string  $name
    * @return void
    */
    public function setDefaultConnection($name)
    {
        $this->defaultConnectionName = $name;
    }

    /**
     * @param string $method
     * @param array  $params
     *
     * @return mixed
     **/
    public function __call($method, $params)
    {
        return call_user_func([$this->connection(), $method], ...$params);
    }
}

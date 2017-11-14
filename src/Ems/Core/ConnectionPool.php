<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 11.11.17
 * Time: 07:39
 */

namespace Ems\Core;

use Ems\Contracts\Core\ConnectionPool as ConnectionPoolContract;
use Ems\Contracts\Core\Connection;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Patterns\ExtendableTrait;

class ConnectionPool implements ConnectionPoolContract
{
    use ExtendableTrait;

    /**
     * @var string
     **/
    protected $defaultConnectionName = 'default';

    /**
     * @var array
     **/
    protected $connections = [];

    /**
     * {@inheritdoc}
     *
     * @param string|UrlContract $nameOrUrl
     *
     * @return Connection
     *
     * @throws HandlerNotFoundException
     **/
    public function connection($nameOrUrl=null)
    {
        $nameOrUrl = $nameOrUrl ?: $this->defaultConnectionName();
        $key = (string)$nameOrUrl;

        if (isset($this->connections[$key])) {
            return $this->connections[$key];
        }

        $connection = $this->callUntilNotNull([$nameOrUrl]);

        if (!$connection instanceof Connection) {
            throw new HandlerNotFoundException("No handler found to create connection '$nameOrUrl'");
        }

        // Assign the created connection to the name AND its url to always
        // return the same connection for one url
        $this->connections[$key] = $connection;
        $this->connections[(string)$connection->url()] = $connection;

        return $connection;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function defaultConnectionName()
    {
        return $this->defaultConnectionName;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return self
     **/
    public function setDefaultConnectionName($name)
    {
        $this->defaultConnectionName = $name;
        return $this;
    }
}
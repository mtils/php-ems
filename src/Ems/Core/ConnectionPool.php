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
use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\InputConnection;
use Ems\Contracts\Core\IO;
use Ems\Contracts\Core\OutputConnection;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Patterns\ExtendableTrait;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

class ConnectionPool implements ConnectionPoolContract, IO
{
    use ExtendableTrait;

    /**
     * PHP does not always define the constants STDIN, STDOUT and STDERR, so
     * here are they it manually
     */
    const STDIN = 'stdin';

    const STDOUT = 'stdout';

    const STDERR = 'stderr';

    /**
     * @var string
     **/
    protected $defaultConnectionName = 'default';

    /**
     * @var array
     **/
    protected $connections = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var callable
     */
    protected $logExtension;

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
        $url = $nameOrUrl instanceof UrlContract ? $nameOrUrl : new Url($nameOrUrl);

        if (isset($this->connections[$key])) {
            return $this->connections[$key];
        }

        $connection = $this->callUntilNotNull([$url]);

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
     * {@inheritDoc}
     *
     * @return InputConnection
     */
    public function in()
    {
        if (isset($this->connections[static::STDIN])) {
            return $this->connections[static::STDIN];
        }
        $connection = $this->connection(static::STDIN);
        if (!$connection instanceof InputConnection) {
            throw new UnexpectedValueException('The handler for stdin must return an instance of InputConnection not ' . Type::of($connection));
        }
        return $connection;
    }

    /**
     * {@inheritDoc}
     *
     * @return OutputConnection
     */
    public function out()
    {
        if (isset($this->connections[static::STDOUT])) {
            return $this->connections[static::STDOUT];
        }
        $connection = $this->connection(static::STDOUT);
        if (!$connection instanceof OutputConnection) {
            throw new UnexpectedValueException('The handler for stdout must return an instance of OutputConnection not ' . Type::of($connection));
        }
        return $connection;
    }

    /**
     * {@inheritDoc}
     *
     * @return LoggerInterface
     */
    public function log()
    {
        if ($this->logger) {
            return $this->logger;
        }

        if (!$this->logExtension) {
            throw new HandlerNotFoundException("No stderr extension was assigned");
        }

        $logger = Lambda::callFast($this->logExtension, [new Url(static::STDERR)]);

        if (!$logger instanceof LoggerInterface) {
            throw new TypeException('Logger has to be instanceof LoggerInterface not ' . Type::of($logger));
        }

        $this->logger = $logger;

        return $this->logger;
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

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param callable $callable
     *
     * @return self
     **/
    public function extend($name, callable $callable)
    {
        if ($name == static::STDERR) {
            $this->logExtension = $callable;
            $this->logger = null;
            return $this;
        }
        $this->_extensions[$name] = $callable;
        return $this;
    }


}
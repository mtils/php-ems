<?php


namespace Ems\Model\Database;

use Closure;
use Ems\Contracts\Core\Type;
use Exception;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\Database\SQLException;
use Ems\Contracts\Model\Database\NativeError;
use Ems\Core\ConfigurableTrait;
use Ems\Core\Patterns\HookableTrait;
use PDO;
use PDOStatement;
use PDOException;
use InvalidArgumentException;
use UnderflowException;

/**
 * Whats this? No idea?
 * One note: All PDO calls are wrapped in closures to return the right exceptions.
 **/
class PDOConnection implements Connection, Configurable, HasMethodHooks
{
    use ConfigurableTrait;
    use HookableTrait;

    /**
     * @var int
     **/
    const KEY_CASE = PDO::ATTR_CASE;

    /**
     * @var int
     **/
    const NULLS = PDO::ATTR_ORACLE_NULLS;

    /**
     * @var int
     **/
    const STRINGIFY_NUMBERS = PDO::ATTR_STRINGIFY_FETCHES;

    // const ATTR_STATEMENT_CLASS Not supported right now

    /**
     * @var int
     **/
    const TIMEOUT = PDO::ATTR_TIMEOUT;

    /**
     * @var int
     **/
//     const AUTOCOMMIT = PDO::ATTR_AUTOCOMMIT; / Not supported by all drivers

    // const ATTR_EMULATE_PREPARES Will perhaps never be supported

    /**
     * @var int
     **/
    const BUFFERED_QUERIES = PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;

    /**
     * @var int
     **/
    const FETCH_MODE = PDO::ATTR_DEFAULT_FETCH_MODE;

    /**
     * @var string
     **/
    const RETURN_LAST_ID = 'RETURN_LAST_ID';

    /**
     * @var string
     **/
    const RETURN_LAST_AFFECTED = 'RETURN_LAST_AFFECTED';

    /**
     * @var PDO
     **/
    protected $resource;

    /**
     * @var Url
     **/
    protected $url;

    /**
     * @var string|object
     **/
    protected $dialect;

    /**
     * @var Closure
     **/
    protected $errorHandler;

    /**
     * @var array
     **/
    protected $defaultOptions = [
        self::KEY_CASE              => PDO::CASE_NATURAL,
        self::NULLS                 => PDO::NULL_NATURAL,
        self::STRINGIFY_NUMBERS     => false,
        self::TIMEOUT               => 0,
        self::FETCH_MODE            => PDO::FETCH_ASSOC,
        self::RETURN_LAST_ID        => true,
        self::RETURN_LAST_AFFECTED  => true
    ];

    public function __construct(Url $url, array $options=[])
    {
        $this->url = $url;
        $this->createErrorHandler();
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     **/
    public function open()
    {
        if (!$this->isOpen()) {
            $this->resource = $this->createPDO($this->url);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     **/
    public function close()
    {
        $this->resource = null;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function isOpen()
    {
        return ($this->resource instanceof PDO);
    }

    /**
     * {@inheritdoc}
     *
     * @return resource|object|null
     **/
    public function resource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     *
     * @return Url
     **/
    public function url()
    {
        return $this->url;
    }

    /**
     * Return the database dialect. Something like SQLITE, MySQL,...
     *
     * @return string (or object with __toString()
     **/
    public function dialect()
    {
        if ($this->dialect) {
            return $this->dialect;
        }

        return $this->url()->scheme;
    }

    /**
     * Assign the dialect.
     *
     * @param string|object $dialect (string or object with __toString)
     *
     * @return self
     **/
    public function setDialect($dialect)
    {
        if (!Type::isStringLike($dialect)) {
            throw new InvalidArgumentException('Dialect hast to be stringlike, not ' . Type::of($dialect));
        }
        $this->dialect = $dialect;
        return $this;
    }

    /**
     * Starts a new transaction.
     *
     * @return bool
     **/
    public function begin()
    {
        return $this->attempt(function () {
            $this->pdo()->beginTransaction();
        });
    }

    /**
     * Commits the last transaction.
     *
     * @return bool
     **/
    public function commit()
    {
        return $this->attempt(function () {
            return $this->pdo()->commit();
        });
    }

    /**
     * Revert the changes of last transaction.
     *
     * @return self
     **/
    public function rollback()
    {
        return $this->attempt(function () {
            return $this->pdo()->rollBack();
        });
    }

    /**
     * Run the callable in an transaction.
     *
     * @param callable $run
     * @param bool     $attempts (default:1)
     *
     * @return bool (if it was successfully commited)
     **/
    public function transaction(callable $run, $attempts=1)
    {

        if ($attempts < 1) {
            throw new InvalidArgumentException("Invalid transaction attempts: $attempts");
        }

        for ($i = 1; $i <= $attempts; $i++) {

            $this->begin();

            try {
                $run($this);
                return $this->commit();
            } catch (SQLLockException $e) {
                $this->rollback();
                continue;
            } catch (\Exception $e) {
                $this->rollback();
                throw $this->convertException($e);
            }
        }

        return false;
    }

    /**
     * Return if currently a transaction is running.
     *
     * @return bool
     **/
    public function isInTransaction()
    {
        if (!$this->resource) {
            return false;
        }
        return $this->resource->inTransaction();
    }

    /**
     * Run a select statement and return the result.
     *
     * @param string|\Ems\Contracts\Core\Stringable $query
     * @param array                                 $binding (optional)
     * @param mixed                                 $fetchMode (optional)
     *
     * @return PDOResult
     *
     * @throws \Ems\Contracts\Core\Errors\UnSupported
     **/
    public function select($query, array $bindings=[], $fetchMode=null)
    {
        $fetchMode = $fetchMode === null
                     ? $this->getOption(static::FETCH_MODE)
                     : $fetchMode;

        $this->callBeforeListeners('select',[$query, $bindings]);

        $statement = $bindings
                     ? $this->prepared($query, $bindings, $fetchMode)
                     : $this->selectRaw($query, $fetchMode);

        $this->callAfterListeners('select',[$query, $bindings, $statement]);

        return new PDOResult($statement, $this);

    }

    /**
     * Run an insert statement.
     *
     * @param string|\Ems\Contracts\Stringable $query
     * @param array                            $binding (optional)
     * @param bool                             $returnLastInsertId (optional)
     *
     * @return int (last inserted id)
     **/
    public function insert($query, array $bindings=[], $returnLastInsertId=null)
    {
        $returnLastInsertId = $returnLastInsertId !== null
                              ? $returnLastInsertId
                              : $this->getOption(static::RETURN_LAST_ID);


        $this->callBeforeListeners('insert',[$query, $bindings]);

        $result = $bindings ? $this->runPrepared($query, $bindings)
                            : $this->writeUnprepared($query);

        $this->callBeforeListeners('insert',[$query, $bindings]);

        return $returnLastInsertId ? $this->lastInsertId() : null;
    }

    /**
     * Run an altering statement.
     *
     * @param string|\Ems\Contracts\Stringable $query
     * @param array                            $binding (optional)
     * @param bool                             $returnAffected (optional)
     *
     * @return int (Number of affected rows)
     **/
    public function write($query, array $bindings=[], $returnAffected=null)
    {
        $returnAffected = $returnAffected !== null
                          ? $returnAffected
                          : $this->getOption(static::RETURN_LAST_AFFECTED);

        $this->callBeforeListeners('write',[$query, $bindings]);

        if (!$bindings) {
            $rows = $this->writeUnprepared($query);
            $this->callAfterListeners('write',[$query, $bindings]);
            return $returnAffected ? $rows : null;
        }

        $statement = $this->runPrepared($query, $bindings);

        $this->callAfterListeners('write',[$query, $bindings]);

        return $returnAffected ? $statement->rowCount() : null;
    }

    /**
     * {@inheritdoc}
     *
     * @param array                            $binding (optional)
     * @param bool                             $returnAffected (optional)
     *
     * @return \Ems\Contracts\Expression\Prepared
     **/
    public function prepare($query, array $bindings=[])
    {
        $statement = $this->statement($query);

        $this->callBeforeListeners('prepare',[$query, $bindings]);

        $prepared = (new PDOPrepared(
            $statement,
            $query,
            $this->getOption(self::RETURN_LAST_AFFECTED),
            $this->errorHandler
        ))->bind($bindings);

        $this->callAfterListeners('prepare',[$query, $bindings, $prepared]);

        return $prepared;

    }

    /**
     * @return array
     **/
    public function methodHooks()
    {
        return ['select', 'insert', 'write', 'prepare'];
    }

    protected function attempt(Closure $run, $query='')
    {
        try {
            return $run();
        } catch (Exception $e) {
            throw $this->convertException($e, $query);
        }
    }

    /**
     * Return the last inserted id.
     *
     * @param string $sequence (optional)
     *
     * @return int (0 on none)
     **/
    public function lastInsertId($sequence=null)
    {
        return $this->pdo()->lastInsertId($sequence);
    }

    /**
     * @return PDO
     **/
    protected function pdo()
    {
        if (!$this->isOpen()) {
            $this->open();
        }
        return $this->resource();
    }

    protected function wasCausedByLock(Exception $e)
    {
        $message = $e->getMessage();
        
    }

    /**
     * @param string|\Ems\Contracts\Stringable $query
     * @param array                            $binding (optional)
     * @param mixed                            $fetchMode (optional)
     *
     * @return PDOStatement
     **/
    protected function runPrepared($query, array $bindings, $fetchMode=null)
    {
        return $this->attempt(function () use ($query, $bindings) {

            $statement = $this->prepared($query, $bindings);
            $statement->execute();

            return $statement;
        }, $query);

    }

    protected function writeUnprepared($query)
    {
        return $this->attempt(function () use ($query) {
            return $this->pdo()->exec("$query");
        }, $query);
    }

    protected function selectRaw($query, $fetchMode)
    {
        return $this->attempt(function () use ($query, $fetchMode) {
            return $this->pdo()->query("$query", $fetchMode);
        }, $query);

    }

    protected function createPDO(Url $url)
    {
        $pdo = new PDO(
            $this->urlToDsn($url),
            $url->user ? $url->user : null,
            $url->password ? $url->password : null
        );

        foreach ($this->supportedOptions() as $option) {
            if (is_int($option)) {
                $pdo->setAttribute($option, $this->getOption($option));
            }
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * @param Url $url
     *
     * @return string
     **/
    protected function urlToDsn(Url $url)
    {

        $driver = $url->scheme;
        $db = $url->path ? $url->path : '';
        $port = $url->port;
        $host = $url->host;

        if ($driver == 'sqlite') {
            return $host == 'memory' ? 'sqlite::memory:' : "sqlite:$db";
        }

        $parts = [];

        if ($db) {
            $parts[] = "dbname=$db";
        }
        if ($host) {
            $parts[] = "host=$host";
        }
        if ($port) {
            $parts[] = "port=$port";
        }
        print_r($parts);
        return implode(';', $parts);
    }

    protected function prepared($query, array $bindings, $fetchMode=null)
    {
        $statement = $this->statement($query, $fetchMode);
        PDOPrepared::bindToStatement($statement, $bindings);
        return $statement;
    }

    /**
     * @param string $query
     * @param int    $fetchMode (optional)
     *
     * @return PDOStatement
     **/
    protected function statement($query, $fetchMode=null)
    {

        return $this->attempt(function () use ($query, $fetchMode) {

            $statement = $this->pdo()->prepare("$query");

            if ($fetchMode !== null) {
                $statement->setFetchMode($fetchMode);
            }
            return $statement;
        }, $query);

    }

    protected function convertException(Exception $e, $query='')
    {
        $code = $e->getCode();
        $code = is_int($code) ? $code : 0;

        if (!$e instanceof PDOException) {
            $msg = 'Unknown exception occured: ' . $e->getMessage();
            return new SQLException($msg, $query, $code, $e);
        }

        if (!$this->dialect instanceof Dialect) {
            return new SQLException($e->getMessage(), $this->toError($e, $query), $code, $e);
        }

        return $this->dialect->createException($this->toError($e, $query), $e);

    }

    protected function toError(PDOException $p, $query='')
    {

        $errorInfo = $p->errorInfo;

        return new NativeError([
            'query'    => $query,
            'sqlState' => isset($errorInfo[0]) ? $errorInfo[0] : 'HY000',
            'code'     => isset($errorInfo[1]) ? $errorInfo[1] : $p->getCode(),
            'message'  => isset($errorInfo[2]) ? $errorInfo[2] : $p->getMessage()
        ]);

    }

    protected function createErrorHandler()
    {
        $this->errorHandler = function (Exception $e, $query) {
            throw $this->convertException($e, $query);
        };
    }
}

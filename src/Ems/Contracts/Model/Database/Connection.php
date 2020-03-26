<?php


namespace Ems\Contracts\Model\Database;

use Ems\Contracts\Core\Connection as BaseConnection;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Expression\Prepared;
use Ems\Contracts\Model\Result;
use Ems\Contracts\Model\SupportsTransactions;
use Ems\Model\Database\Query as QueryObject;

/**
 * For database connection urls see the Engine configuration of SQLAlchemy:
 *
 * @see http://docs.sqlalchemy.org/en/latest/core/engines.html
 *
 * @example postgresql://scott:tiger@localhost:5432/mydatabase
 * @example sqlite:///var/lib/sqlite/my.db
 * @example mysql://scott:tiger@localhost:3306/mydatabase
 **/
interface Connection extends BaseConnection, SupportsTransactions
{

    /**
     * Return the database dialect. Something like SQLITE, MySQL,...
     *
     * @return string|Dialect (or object with __toString()
     **/
    public function dialect();

    /**
     * Starts a new transaction.
     *
     * @return bool
     **/
    public function begin();

    /**
     * Commits the last transaction.
     *
     * @return bool
     **/
    public function commit();

    /**
     * Revert the changes of last transaction.
     *
     * @return bool
     **/
    public function rollback();

    /**
     * Run a select statement and return the result.
     *
     * @param string|Stringable $query
     * @param array             $bindings (optional)
     * @param mixed             $fetchMode (optional)
     *
     * @return Result
     **/
    public function select($query, array $bindings = [], $fetchMode = null);

    /**
     * Run an insert statement.
     *
     * @param string|Stringable $query
     * @param array                                 $bindings (optional)
     * @param bool                                  $returnLastInsertId (optional)
     *
     * @return int (last inserted id)
     **/
    public function insert($query, array $bindings = [], $returnLastInsertId = null);

    /**
     * Run an altering statement.
     *
     * @param string|Stringable $query
     * @param array                                 $bindings (optional)
     * @param bool                                  $returnAffected (optional)
     *
     * @return int (Number of affected rows)
     **/
    public function write($query, array $bindings = [], $returnAffected = null);

    /**
     * Create a prepared statement.
     *
     * @param string|Stringable $query
     * @param array                                 $bindings (optional)
     *
     * @return Prepared
     **/
    public function prepare($query, array $bindings = []);

    /**
     * Return the last inserted id.
     *
     * @param string $sequence (optional)
     *
     * @return int (0 on none)
     **/
    public function lastInsertId($sequence = null);

    /**
     * Create a new query.
     *
     * @param string $table (optional)
     *
     * @return QueryObject
     */
    public function query($table = null);
}


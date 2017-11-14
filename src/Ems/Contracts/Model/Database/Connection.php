<?php


namespace Ems\Contracts\Model\Database;

use Ems\Contracts\Core\Connection as BaseConnection;

/**
 * For database connection urls see the Engine configration of SQLAlchemy:
 *
 * @see http://docs.sqlalchemy.org/en/latest/core/engines.html
 *
 * @example postgresql://scott:tiger@localhost:5432/mydatabase
 * @example sqlite:///var/lib/sqlite/my.db
 * @example mysql://scott:tiger@localhost:3306/mydatabase
 **/
interface Connection extends BaseConnection
{

    /**
     * Return the database dialect. Something like SQLITE, MySQL,...
     *
     * @return string (or object with __toString()
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
     * Run the callable in an transaction.
     *
     * @param callable $run
     * @param bool     $attempts (default:1)
     *
     * @return bool (if it was successfully commited)
     **/
    public function transaction(callable $run, $attempts=1);

    /**
     * Return if currently a transaction is running.
     *
     * @return bool
     **/
    public function isInTransaction();

    /**
     * Run a select statement and return the result.
     *
     * @param string|\Ems\Contracts\Stringable $query
     * @param array                            $binding (optional)
     * @param mixed                            $fetchMode (optional)
     *
     * @return Result
     **/
    public function select($query, array $bindings=[], $fetchMode=null);

    /**
     * Run an insert statement.
     *
     * @param string|\Ems\Contracts\Stringable $query
     * @param array                            $binding (optional)
     * @param bool                             $returnLastInsertId (optional)
     *
     * @return int (last inserted id)
     **/
    public function insert($query, array $bindings=[], $returnLastInsertId=null);

    /**
     * Run an altering statement.
     *
     * @param string|\Ems\Contracts\Stringable $query
     * @param array                            $binding (optional)
     * @param bool                             $returnAffected (optional)
     *
     * @return int (Number of affected rows)
     **/
    public function write($query, array $bindings=[], $returnAffected=null);

    /**
     * Create a prepared statement.
     *
     * @param array                            $binding (optional)
     * @param bool                             $returnAffected (optional)
     *
     * @return \Ems\Contracts\Expression\Prepared
     **/
    public function prepare($query, array $bindings=[]);

    /**
     * Return the last inserted id.
     *
     * @param string $sequence (optional)
     *
     * @return int (0 on none)
     **/
    public function lastInsertId($sequence=null);

}

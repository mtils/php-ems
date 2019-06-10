<?php
/**
 *  * Created by mtils on 08.06.19 at 06:45.
 **/

namespace Ems\Model\Database\Storages;


use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Model\Database\SQL;

trait DatabaseStorageTrait
{
    /**
     * @var Connection
     **/
    protected $connection;

    /**
     * @var Dialect
     */
    protected $dialect;

    /**
     * @var StorageDriver
     */
    protected $driver;

    /**
     * @var string
     **/
    protected $table = 'blob_entries';

    /**
     * @var string
     **/
    protected $quotedTable = '';

    /**
     * @var string
     **/
    protected $idKey = 'id';

    /**
     * @var string
     **/
    protected $quotedIdKey = '';

    /**
     * @var string
     */
    protected $selectColumnString = '*';

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see self::FILESYSTEM
     * @see self::SQL
     * @see self::NOSQL
     **/
    public function storageType()
    {
        return self::SQL;
    }

    /**
     * A buffered storage needs an manual call to persist() to write the state.
     * An unbuffered storage just writes on every change.
     *
     * @return bool
     */
    public function isBuffered()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getIdKey()
    {
        return $this->idKey;
    }

    /**
     * @param string $idKey
     *
     * @return $this
     */
    public function setIdKey($idKey)
    {
        $this->idKey = $idKey;
        $this->quotedIdKey = $this->quote($idKey, 'name');
        $this->reconfigureDriverIfExists();
        return $this;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set the table for the queries.
     *
     * @param string $table
     *
     * @return $this
     **/
    public function setTable($table)
    {
        $this->table = $table;
        $this->quotedTable = $this->quote($table, 'name');
        $this->reconfigureDriverIfExists();
        return $this;
    }

    /**
     * @return StorageDriver
     */
    public function getDriver()
    {
        if (!$this->driver) {
            $driver = $this->createStorageDriver($this->connection, $this->dialect());
            $this->configureStorageDriver($driver);
            $this->setDriver($driver);
        }
        return $this->driver;
    }

    /**
     * @param StorageDriver $driver
     *
     * @return $this
     */
    public function setDriver(StorageDriver $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Shortcut for Dialect::quote just to make the code more readable.
     *
     * @param string $string
     * @param string $type (default: string) Can be string|name
     *
     * @return string
     **/
    protected function quote($string, $type='string')
    {
        return $this->dialect()->quote($string, $type);
    }

    /**
     * @return Dialect
     */
    protected function dialect()
    {
        if ($this->dialect) {
            return $this->dialect;
        }
        $dialect = $this->connection->dialect();
        if (!$dialect instanceof Dialect) {
            $dialect = SQL::dialect($dialect);
        }
        $this->dialect = $dialect;
        return $this->dialect;
    }

    /**
     * Overwrite this method to return the "column" part of the select one
     * query
     *
     * @return string
     */
    protected function getSelectColumnString()
    {
        return $this->selectColumnString;
    }

    protected function createStorageDriver(Connection $connection, Dialect $dialect)
    {
        $driver = new StorageDriver($connection, $dialect);
        return $driver;
    }

    protected function configureStorageDriver(StorageDriver $driver)
    {
        $driver->configure(
            $this->table,
            $this->idKey,
            $this->getSelectColumnString()
        );
    }

    protected function reconfigureDriverIfExists()
    {
        if ($this->driver) {
            $this->configureStorageDriver($this->driver);
        }
    }

}
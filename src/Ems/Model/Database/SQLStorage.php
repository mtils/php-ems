<?php

namespace Ems\Model\Database;

use Ems\Contracts\Core\BufferedStorage;
use Ems\Contracts\Expression\Prepared;
use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\QueryableStorage;
use Ems\Core\ArrayWithState;
use Ems\Core\Collections\StringList;
use Ems\Core\Support\TrackedArrayDataTrait;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Model\Database\SQLNameNotFoundException;
use Ems\Model\StorageQuery;
use Ems\Model\GenericResult;
use IteratorAggregate;


/**
 * The SQLStorage is a storage which saves the data
 * in a sql database.
 * You can only store arrays in that storage.
 * Every array has to have the same keys and cannot
 * contain the id key.
 * For performance reasons the replace query to
 * replace the data in the database is prepared once.
 * It does NOT CHECK anything and let you run into any
 * database errors for performance reasons.
 * It also does not cache any data from the database, use a Proxy for
 * cache.
 **/
class SQLStorage extends ArrayWithState implements QueryableStorage, BufferedStorage, IteratorAggregate
{
    /**
     * @var Connection
     **/
    protected $connection;

    /**
     * @var Dialect
     **/
    protected $dialect;

    /**
     * @var string
     **/
    protected $table = '';

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
     * @var Prepared
     **/
    protected $selectOneStatement;

    /**
     * @var Prepared
     **/
    protected $replaceStatement;

    /**
     * @var array
     **/
    protected $dataKeys = [];

    /**
     * @var callable
     **/
    protected $tableCreator;

    /**
     * The allowed operators for searches.
     *
     * @var array
     **/
    protected $allowedOperators = ['=', '<>', '!=', 'in', '<', '>', '>=', '<=', 'like'];

    /**
     * @var callable
     **/
    protected $selector;

    /**
     * @var callable
     **/
    protected $purger;

    /***
     * @param Connection $connection
     * @param string     $table
     * @param string     $idKey
     **/
    public function __construct(Connection $connection, $table='', $idKey='id')
    {
        $this->setConnection($connection);
        $this->setTable($table);
        $this->setIdKey($idKey);
        $this->assignQueryProxies();
    }

    /**
     * Return the assigned connection.
     *
     * @return Connection
     **/
    public function connection()
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool (if successfull)
     **/
    public function persist()
    {
        if (!$this->wasModified()) {
            return false;
        }

        foreach ($this->getModifiedData() as $id=>$data) {
            $this->writeToDB($id, $data);
        }

        if ($unsettedKeys = $this->getUnsettedKeys()) {
            $this->deleteFromDB($unsettedKeys);
        }

        $this->_originalAttributes = $this->_attributes;

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $keys (optional)
     *
     * @return bool (if successfull)
     **/
    public function purge(array $keys=null)
    {
        $this->bootOnce();

        if ($keys === null) {
            $this->clear();
            $this->_originalAttributes = [];
            $this->deleteFromDB();
            return true;
        }

        if (!$keys) {
            return false;
        }

        $this->deleteFromDB($keys);
        $this->deleteFromArrays($keys);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * Queries work only if the data has been persisted....
     *
     *
     * @param string|\Ems\Constracts\Core\Expression|\Closure $operand
     * @param mixed                                           $operatorOrValue (optional)
     * @param mixed                                           $value
     *
     * @return StorageQuery
     **/
    public function where($operand, $operatorOrValue=null, $value=null)
    {
        $numArgs = func_num_args();
        $query = $this->newQuery();

        if ($numArgs == 1) {
            return $query->where($operand);
        }

        if ($numArgs == 2) {
            return $query->where($operand, $operatorOrValue);
        }

        return $query->where($operand, $operatorOrValue, $value);

    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function storageType()
    {
        return self::SQL;
    }

    /**
     * Return the name of the queried table.
     *
     * @return string
     **/
    public function table()
    {
        return $this->table;
    }

    /**
     * This is a helper mechanism if you work with dynamically created databases
     * (like with sqlite) and tables. You can assign a table creator, wich will
     * create the table in the database if it does not exist.
     * If you assign a callable here the Storage will ping the storage once
     * with a select statement before it does any call to the database.
     * So at the end you will get one query more for the lifecycle of this object
     * if you use this mechanism.
     *
     * @param callable $creator
     *
     * @return self
     **/
    public function createTableBy(callable $creator)
    {
        $this->tableCreator = $creator;
        return $this;
    }

    /**
     * Set the table for the queries.
     *
     * @param string $table
     *
     * @return self
     **/
    protected function setTable($table)
    {
        $this->table = $table;
        $this->quotedTable = $this->dialect->quote($table, 'name');
        return $this;
    }

    /**
     * Return the name of the "id" column.
     *
     * @return string
     **/
    public function idKey()
    {
        return $this->idKey;
    }

    /**
     * Return an array of key (strings)
     *
     * @return StringList
     *
     * @see \Ems\Contracts\Core\HasKeys
     **/
    public function keys()
    {
        $this->bootOnce();

        $uniqueKeys = [];

        $unsettedKeys = $this->getUnsettedKeys();

        foreach (array_keys($this->_attributes) as $key) {
            $uniqueKeys[$key] = true;
        }

        foreach ($this->select(null, $this->quotedIdKey) as $row) {

            $key = $row[$this->idKey];

            if (in_array($key, $unsettedKeys)) {
                continue;
            }

            $uniqueKeys[$key] = true;;
        }

        return new StringList(array_keys($uniqueKeys));
    }

    /**
     * @return \Iterator
     **/
    public function getIterator()
    {
        foreach ($this->keys() as $key) {
            $row = $this->offsetGet($key);
            yield $key => $row;
        }
    }

    /**
     * {@inheritdoc}
     *
     * CAUTION: This method can eat your memory, use self::getIterator()
     * 
     * @return \Iterator
     **/
    public function toArray()
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * Set the name of the "id" column.
     *
     * @param string $idKey
     *
     * @return self
     **/
    protected function setIdKey($idKey)
    {
        $this->idKey = $idKey;
        $this->quotedIdKey = $this->dialect->quote($idKey, 'name');
        return $this;
    }

    /**
     * Loads the entry from db if not previously done.
     *
     * @param string $key
     **/
    public function offsetExists($key)
    {
        if (parent::offsetExists($key)) {
            return true;
        }

        // if it is in original and not in attributes
        // it was deleted
        if (isset($this->_originalAttributes[$key])) {
            return false;
        }

        if (!$data = $this->getFromDB($key)) {
            return false;
        }

        $this->_originalAttributes[$key] = $data;
        $this->_attributes[$key] = $data;

        return true;

    }

    /**
     * Loads the entry from db if not previously done.
     *
     * @param string $key
     **/
    public function offsetGet($key)
    {
        $this->offsetExists($key); // Trigger all the loading stuff
        return parent::offsetGet($key);
    }

    /**
     * Load the entry from DB to now that it was deleted.
     *
     * @param mixed $offset
     **/
    public function offsetUnset($offset)
    {
        $this->offsetExists($offset); // Trigger loading
        return parent::offsetUnset($offset);
    }

    /**
     * Do a quick select to check if the database and table connection works.
     *
     **/
    protected function bootOnce()
    {
        if ($this->_booted) {
            return;
        }

        parent::bootOnce();

        $this->autoCreateTable();
    }

    /**
     * Try to create the table if a creator was assigned.
     *
     * @return bool
     **/
    protected function autoCreateTable()
    {
        // Without a table creator we cannot create the table
        if (!$this->tableCreator) {
            return;
        }

        // Do a little test query
        try {

            $this->connection->select("SELECT {$this->quotedIdKey} FROM {$this->quotedTable} LIMIT 1");
            return true;

        } catch (SQLNameNotFoundException $e) { // The only matching Exception in this case
        }

        if ($e->missingType != 'table') {
            throw $e;
        }

        call_user_func($this->tableCreator, $this->connection, $this->table, $this->idKey);

        return true;
    }

    protected function getFromDB($id)
    {
        $data = $this->selectOneStatement()->bind(['id'=>$id])->first();
        unset($data[$this->idKey]);
        return $data;
    }

    protected function writeToDB($id, $data)
    {
        $data[$this->idKey] = $id;
        $this->replaceStatement($data)->write($data, false);
    }

    /**
     * Delete the passed $id(s) from db. If non passed delete all, if one or an
     * array the passed ids.
     *
     * @param mixed $ids (optional)
     **/
    protected function deleteFromDB($ids=null)
    {

        $query = "DELETE FROM {$this->quotedTable}";

        if ($ids == null) {
            $this->connection->write($query, [], false);
            return;
        }

        $query .= " WHERE {$this->quotedIdKey} IN ";

        $quotedIds = array_map(function ($id) {
            return $this->dialect->quote($id, 'string');
        },(array)$ids);

        $this->connection->write($query . "\n(" . implode(',', $quotedIds) . ')', [], false);

    }

    protected function selectOneStatement()
    {
        if ($this->selectOneStatement) { 
            return $this->selectOneStatement;
        }

        $query = "SELECT * FROM {$this->quotedTable} WHERE {$this->quotedIdKey} = :id";
        $this->selectOneStatement = $this->connection->prepare($query);

        return $this->selectOneStatement;
    }

    protected function replaceStatement(array $array)
    {
        if ($this->replaceStatement) {
            return $this->replaceStatement;
        }

        $columns =  array_keys($array);

        $quotedColumns = array_map(function ($key) {
            return $this->dialect->quote($key, 'name');
        }, $columns);

        $placeHolders = array_map(function ($key) {
            return ":$key";
        }, $columns);

        $query  = "REPLACE INTO {$this->quotedTable}\n";
        $query .= '(' . implode(', ', $quotedColumns) . ")\n";
        $query .= "VALUES\n(" . implode(', ', $placeHolders) . ')';

        $this->replaceStatement = $this->connection->prepare($query);

        return $this->replaceStatement;
    }

    protected function selectArray(StorageQuery $query)
    {

        $results = [];

        foreach ($this->select($query, '*') as $row) {
            $id = $row[$this->idKey];
            unset($row[$this->idKey]);
            $results[$id] = $row;
        }

        return $results;
    }

    protected function purgeByQuery(StorageQuery $query)
    {
        $ids = [];

        foreach ($this->select($query, $this->quotedIdKey) as $row) {
            $ids[] = $row[$this->idKey];
        }

        $this->deleteFromArrays($ids);

        $this->deleteFromDB($ids);

        return true;

    }

    protected function select(StorageQuery $query=null, $columns='*')
    {
        $queryString  = "SELECT $columns FROM {$this->quotedTable}";
        $bindings = [];

        if ($query && $wherePart = $this->dialect->render($query, $bindings)) {
            $queryString .= "\nWHERE $wherePart";
        }

        return $this->connection->select($queryString, $bindings);

    }

    /**
     * @return StorageQuery
     **/
    protected function newQuery()
    {
        return (new StorageQuery($this->selector, $this->purger))
              ->allowConnectives('and', 'or')
              ->allowOperators($this->allowedOperators)
              ->forbidNesting()
              ->forbidMultipleConnectives();

    }

    protected function assignQueryProxies()
    {
        $this->selector = function ($query) {
            return $this->selectArray($query);
        };

        $this->purger = function ($query) {
            return $this->purgeByQuery($query);
        };
    }

    protected function deleteFromArrays($keys)
    {
        // First delete it from the arrays
        foreach ($keys as $key) {
            if (isset($this->_originalAttributes[$key])) {
                unset($this->_originalAttributes[$key]);
            }
            if (isset($this->_attributes[$key])) {
                unset($this->_attributes[$key]);
            }
        }
    }

    protected function setConnection(Connection $connection)
    {
        $dialect = $connection->dialect();

        if (!$dialect instanceof Dialect) {
            throw new UnConfiguredException("The passed connection has to have a dialect object before adding it");
        }

        $this->dialect = $dialect;
        $this->connection = $connection;
    }
}

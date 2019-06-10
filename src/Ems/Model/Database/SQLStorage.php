<?php

namespace Ems\Model\Database;

use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\QueryableStorage;
use Ems\Core\ArrayWithState;
use Ems\Core\Collections\StringList;
use Ems\Model\Database\Storages\DatabaseStorageTrait;
use Ems\Model\StorageQuery;
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
class SQLStorage extends ArrayWithState implements QueryableStorage, IteratorAggregate
{
    use DatabaseStorageTrait;

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
        $this->connection = $connection;
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
     * @inheritDoc
     */
    public function isBuffered()
    {
        return true;
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

        foreach ($this->getDriver()->keys() as $key) {

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
     * Loads the entry from db if not previously done.
     *
     * @param string $key
     *
     * @return bool
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
     *
     * @return mixed
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
     *
     * @return void
     **/
    public function offsetUnset($offset)
    {
        $this->offsetExists($offset); // Trigger loading
        parent::offsetUnset($offset);
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
            return false;
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
        $data = $this->getDriver()->selectOne($id);
        unset($data[$this->idKey]);
        return $data;
    }

    protected function writeToDB($id, $data)
    {
        $data[$this->idKey] = $id;
        $this->getDriver()->replace($data);
    }

    /**
     * Delete the passed $id(s) from db. If non passed delete all, if one or an
     * array the passed ids.
     *
     * @param mixed $ids (optional)
     **/
    protected function deleteFromDB($ids=null)
    {
        $this->getDriver()->delete($ids === null ? '*' : $ids);
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

        if ($query && $wherePart = $this->dialect()->render($query, $bindings)) {
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

}

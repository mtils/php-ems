<?php
/**
 *  * Created by mtils on 08.06.19 at 08:35.
 **/

namespace Ems\Model\Database\Storages;

use function array_keys;
use function array_map;
use function count;
use Ems\Contracts\Expression\Prepared;
use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Core\Exceptions\DataIntegrityException;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Model\Database\SQL;
use function implode;
use function is_array;

class StorageDriver
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Dialect
     */
    protected $dialect;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var string
     */
    protected $quotedTable = '';

    /**
     * @var string
     */
    protected $idKey = '';

    /**
     * @var string
     */
    protected $quotedIdKey = '';

    /**
     * @var string
     */
    protected $selectColumnString = '';

    /**
     * @var array
     */
    protected $discriminators;

    /**
     * @var Prepared[]
     */
    protected $statements = [];

    public function __construct(Connection $connection, Dialect $dialect)
    {
        $this->connection = $connection;
        $this->dialect = $dialect;
    }

    /**
     * Return if a key exists in the database.
     *
     * @param $key
     * @return bool
     */
    public function exists($key)
    {
        return (bool)$this->existsStatement()->bind(['id' => $key])->first();

    }

    /**
     * Select one entry (n columns) from the database.
     *
     * @param string $key
     *
     * @return array|null
     */
    public function selectOne($key)
    {
        return $this->selectOneStatement($this->selectColumnString)
                    ->bind(['id' => $key])->first();
    }

    /**
     * Perform an insert statement and return the inserted ID.
     *
     * @param array $values
     *
     * @return int
     */
    public function insert(array $values)
    {
        if (!$this->insertStatement(array_keys($values))
             ->write($values, true)) {
            throw new DataIntegrityException('Insert did fail');
        };
        return $this->connection->lastInsertId();
    }

    /**
     * Perform a REPLACE query in the database. (INSERT if a row with the passed
     * primary key does not exist, if it exists, delete it prior to the insert
     * and accept the passed primary key)
     *
     * @param array $values
     */
    public function replace(array $values)
    {
        $this->replaceStatement(array_keys($values))
             ->write($values, false);
    }

    /**
     * Update row $id with $values. Optionally pass a awaited of amount of
     * affected rows. If this will not be fulfilled an Exception will be thrown.
     *
     * @param int|string $key
     * @param array      $values
     * @param int        $forceAffected (default:0)
     *
     * @return int
     */
    public function update($key, array $values, $forceAffected=0)
    {
        $statement = $this->updateStatement(array_keys($values));
        $values[$this->idKey] = $key;
        $affected = $statement->write($values, (bool)$forceAffected);

        if (!$forceAffected) {
            return -1;
        }

        if ($affected == 0) {
            throw new KeyNotFoundException("The key $key does not exist");
        }

        if ($affected != $forceAffected) {
            throw new DataIntegrityException("Update should update $forceAffected rows but affected $affected.");
        }

        return $affected;

    }

    /**
     * Delete the passed key(s). Optionally force to have $forceAffected deletions.
     * @see self::update()
     *
     * @param array $keys
     * @param int $forceAffected (default:0)
     *
     * @return int
     */
    public function delete($keys=[], $forceAffected=0)
    {

        $result = $this->deleteAndOptionallyReturnAffected($keys, (bool)$forceAffected);

        if (!$forceAffected) {
            return -1;
        }

        if ($result == 0) {
            $keyString = is_array($keys) ? implode(',', $keys) : $keys;
            throw new KeyNotFoundException("The key '$keyString' does not exist");
        }

        if ($result != $forceAffected) {
            throw new DataIntegrityException("Delete should delete $forceAffected rows but deleted $result.");
        }

        return $result;
    }

    public function keys()
    {
        $query = "SELECT {$this->quotedIdKey} FROM {$this->quotedTable}";

        if($this->discriminators) {
            $query .= ' WHERE ' . SQL::renderColumnsForWhere($this->dialect, $this->discriminators);
        }

        $keys = [];
        foreach ($this->connection->select($query) as $row) {
            $keys[] = $row[$this->idKey];
        }
        return $keys;

    }

    public function configure($table, $idKey, $selectColumnString='*', $discriminators=[])
    {
        $this->table = $table;
        $this->quotedTable = $this->dialect->quote($table, 'name');
        $this->idKey = $idKey;
        $this->quotedIdKey = $this->dialect->quote($idKey, 'name');
        $this->selectColumnString = $selectColumnString;
        $this->discriminators = $discriminators;
    }

    protected function deleteAndOptionallyReturnAffected($keys, $returnAffected=false)
    {
        $keys = (array)$keys;

        if (count($keys) == 1 && $keys !== ['*']) {
            return $this->deleteOneStatement()->write(['id' => $keys[0]], $returnAffected);
        }

        $query = "DELETE FROM {$this->quotedTable}";

        $where = 'WHERE';

        if($this->discriminators) {
            $query .= ' WHERE ' . SQL::renderColumnsForWhere($this->dialect, $this->discriminators);
            $where = 'AND';
        }

        if ($keys === ['*']) {
            return $this->connection->write($query, [], $returnAffected);
        }

        $query .= " $where {$this->quotedIdKey} IN ";

        $quotedKeys = array_map(function ($id) {
            return $this->dialect->quote($id, 'string');
        },(array)$keys);

        return $this->connection->write($query . "\n(" . implode(',', $quotedKeys) . ')', [], $returnAffected);
    }

    /**
     * Return the statement that is used for isset() queries.
     *
     * @return Prepared
     */
    protected function existsStatement()
    {
        return $this->selectOneStatement($this->quotedIdKey);
    }

    /**
     * @param string $columnString
     *
     * @return Prepared
     */
    protected function selectOneStatement($columnString='*')
    {
        $cacheKey = 'selectOne_' . $columnString;

        // table, columns, idKey, discriminator?
        if (isset($this->statements[$cacheKey])) {
            return $this->statements[$cacheKey];
        }

        $query = "SELECT $columnString FROM {$this->quotedTable} WHERE {$this->quotedIdKey} = :id";

        if($this->discriminators) {
            $query .= ' AND ' . SQL::renderColumnsForWhere($this->dialect, $this->discriminators);
        }

        $this->statements[$cacheKey] = $this->connection->prepare($query);

        return $this->statements[$cacheKey];
    }

    /**
     * Return the statement that is used for replace calls.
     *
     * @param array $keys
     *
     * @return Prepared
     */
    protected function replaceStatement(array $keys)
    {
        // table, columnNames, discriminator?
        return $this->insertOrReplaceStatement($keys, 'REPLACE');
    }

    /**
     * Return the statement that is used for insert calls.
     *
     * @param array $keys
     *
     * @return Prepared
     */
    protected function insertStatement(array $keys)
    {
        // table, columnNames, discriminator?
        return $this->insertOrReplaceStatement($keys, 'INSERT');
    }

    /**
     * Return the statement that is used for update calls.
     *
     * @param array $keys
     *
     * @return Prepared
     */
    protected function updateStatement(array $keys)
    {
        // table, idKey, columnNames, discriminator?
        $cacheKey = 'update_' . implode('-', $keys);

        if (isset($this->statements[$cacheKey])) {
            return $this->statements[$cacheKey];
        }

        $columns = [];

        foreach ($keys as $key) {
            $columns[$key] = SQL::raw(":$key");
        }

        $query = "UPDATE " . $this->quotedTable . "
                  SET " . SQL::renderColumnsForUpdate($this->dialect, $columns) . "
                  WHERE {$this->quotedIdKey} = :id";

        if($this->discriminators) {
            $query .= ' AND ' . SQL::renderColumnsForWhere($this->dialect, $this->discriminators);
        }

        $this->statements[$cacheKey] = $this->connection->prepare($query);

        return $this->statements[$cacheKey];
    }

    /**
     * Return the statement that is used to delete onw row.
     *
     * @return Prepared
     */
    protected function deleteOneStatement()
    {
        // table, idKey, discriminator?
        $cacheKey = 'deleteOne';

        // table, columns, idKey, discriminator?
        if (isset($this->statements[$cacheKey])) {
            return $this->statements[$cacheKey];
        }

        $query = "DELETE FROM {$this->quotedTable} WHERE {$this->quotedIdKey} = :id";

        if($this->discriminators) {
            $query .= ' AND ' . SQL::renderColumnsForWhere($this->dialect, $this->discriminators);
        }

        $this->statements[$cacheKey] = $this->connection->prepare($query);

        return $this->statements[$cacheKey];
    }

    /**
     * Build an insert or replace statement (which look almost the same)
     *
     * @param array  $keys
     * @param string $action
     *
     * @return Prepared
     */
    protected function insertOrReplaceStatement(array $keys, $action)
    {
        // table, columnNames, discriminator?

        $cacheKey = $action .'_' . implode('-', $keys);

        if (isset($this->statements[$cacheKey])) {
            return $this->statements[$cacheKey];
        }

        $quotedColumns = array_map(function ($key) {
            return $this->dialect->quote($key, 'name');
        }, $keys);

        $placeHolders = array_map(function ($key) {
            return ":$key";
        }, $keys);

        if ($this->discriminators) {
            foreach ($this->discriminators as $key=>$value) {
                $quotedColumns[] = $this->dialect->quote($key, 'name');
                $placeHolders[] = $this->dialect->quote($value);
            }
        }

        $query  = "$action INTO {$this->quotedTable}\n";
        $query .= '(' . implode(', ', $quotedColumns) . ")\n";
        $query .= "VALUES\n(" . implode(', ', $placeHolders) . ')';

        $this->statements[$cacheKey] = $this->connection->prepare($query);

        return $this->statements[$cacheKey];
    }
}
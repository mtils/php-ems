<?php
/**
 *  * Created by mtils on 22.05.19 at 11:00.
 **/

namespace Ems\Model\Database;


use Ems\Contracts\Core\PushableStorage;
use Ems\Contracts\Core\Serializer;
use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Core\Collections\OrderedList;
use Ems\Core\Exceptions\DataIntegrityException;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Serializer\JsonSerializer;

/**
 * Class SQLBlobStorage
 *
 * This class can be used to put any data into a database without the need to
 * migrate or alter databases.
 * It takes a table (id, resource_name, data) and puts every $offset as an id
 * into this table.
 * The resource_name is typically used to store many different storage in one
 * table.
 *
 * @package Ems\Model\Database
 */
class SQLBlobStorage implements PushableStorage
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
     * @var Serializer
     */
    protected $serializer;

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
    protected $blobKey = 'data';

    /**
     * @var string
     */
    protected $quotedBlobKey = '';

    /**
     * @var string
     */
    protected $discriminator;

    /**
     * @var string
     */
    protected $discriminatorKey = 'resource_name';

    /**
     * SQLBlobStorage constructor.
     *
     * @param Connection $connection
     * @param string $table
     * @param string $blobKey
     */
    public function __construct(Connection $connection, $table = 'blob_entries', $blobKey='data')
    {
        $this->connection = $connection;
        $this->dialect = SQL::dialect($connection->dialect()); // TODO Defer this!
        $this->setTable($table);
        $this->setBlobKey($blobKey);
        $this->setIdKey($this->idKey); // Just to set the quotedBlobKey
        $this->serializer = (new JsonSerializer())->asArrayByDefault();
    }

    /**
     * Add Data
     *
     * @param mixed $value
     *
     * @return string|int
     */
    public function offsetPush($value)
    {
        $encodedValue = $this->serializer->serialize($value);

        $values = [$this->blobKey => $encodedValue];

        $this->restrictIfNeeded($values);

        $valueString = SQL::renderColumnsForInsert($this->dialect, $values);

        $res = $this->connection->insert(
            "INSERT INTO {$this->quotedTable} $valueString"
        );

        if ($res) {
            return $res;
        }
        throw new DataIntegrityException("Couldnt create entry in database");
    }


    /**
     * {@inheritDoc}
     *
     * @return boolean true on success or false on failure.
     **/
    public function offsetExists($offset)
    {
        $where = $this->buildWhere($offset);

        $res = $this->connection->select(
                "SELECT " . $this->quotedIdKey . "
                 FROM " . $this->quotedTable . "
                 WHERE " . SQL::renderColumnsForWhere($this->dialect, $where)
        );
        return (bool)$res->first()[$this->idKey];
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        $where = $this->buildWhere($offset);

        $res = $this->connection->select(
            "SELECT " . $this->quotedBlobKey . "
                 FROM " . $this->quotedTable . "
                 WHERE " . SQL::renderColumnsForWhere($this->dialect, $where)
        );
        return $this->serializer->deserialize($res->first()[$this->blobKey]);
    }

    /**
     * {@inheritDoc}
     *
     * This only works with existing IDs
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $value = $this->serializer->serialize($value);

        $where = $this->buildWhere($offset);

        $query = "UPDATE " . $this->quotedTable . "
                  SET " . SQL::renderColumnsForUpdate($this->dialect, [$this->blobKey => $value]) . "
                  WHERE " . SQL::renderColumnsForWhere($this->dialect, $where);

        $updated = $this->connection->write($query);

        if ($updated == 1) {
            return;
        }

        if ($updated > 1) {
            throw new DataIntegrityException("More than one rows got updated by offsetSet($offset)");
        }

        throw new KeyNotFoundException("The offset $offset does not exist");

    }

    /**
     * {@inheritDoc}
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset The offset to unset.
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        $where = $this->buildWhere($offset);

        $query = "DELETE FROM {$this->quotedTable}
                  WHERE " . SQL::renderColumnsForWhere($this->dialect, $where);

        $deleted = $this->connection->write($query);

        if ($deleted == 1) {
            return;
        }

        if ($deleted > 1) {
            throw new DataIntegrityException("More than one rows got deleted by offsetUnset($offset)");
        }

        throw new KeyNotFoundException("The offset $offset does not exist");

    }

    /**
     * {@inheritDoc}
     *
     * @param array $keys (optional)
     *
     * @return bool (if successful)
     **/
    public function purge(array $keys = null)
    {
        if ($keys === []) {
            return false;
        }

        $where = [];
        $this->restrictIfNeeded($where);

        if ($keys) {
            $where[$this->idKey] = $keys;
        }

        $query = "DELETE FROM {$this->quotedTable}
                  WHERE " . SQL::renderColumnsForWhere($this->dialect, $where);

        return (bool)$this->connection->write($query);
    }

    /**
     * {@inheritDoc}
     *
     * @return bool (if successful)
     **/
    public function persist()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @param array $keys (optional)
     *
     * @return self
     **/
    public function clear(array $keys = null)
    {
        $this->purge($keys);
        return $this;
    }

    /**
     * Return an list keys (should be strings)
     *
     * @return \Ems\Core\Collections\OrderedList
     **/
    public function keys()
    {
        $keys = [];

        $where = [];
        $this->restrictIfNeeded($where);

        $res = $this->connection->select(
            "SELECT " . $this->quotedIdKey . "
                 FROM " . $this->quotedTable . "
                 WHERE " . SQL::renderColumnsForWhere($this->dialect, $where)
        );

        foreach ($res as $row) {
            $keys[] = $row[$this->idKey];
        }
        return new OrderedList($keys);

    }

    /**
     * {@inheritDoc}
     *
     * @return array
     **/
    public function toArray()
    {
        $array = [];

        $where = [];
        $this->restrictIfNeeded($where);

        $res = $this->connection->select(
            "SELECT " . $this->quotedIdKey . ", " . $this->quotedBlobKey . "
                 FROM " . $this->quotedTable . "
                 WHERE " . SQL::renderColumnsForWhere($this->dialect, $where)
        );

        foreach ($res as $row) {
            $array[$row[$this->idKey]] = $this->serializer->deserialize($row[$this->blobKey]);
        }

        return $array;

    }

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
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set the table for the queries.
     *
     * @param string $table
     *
     * @return self
     **/
    public function setTable($table)
    {
        $this->table = $table;
        $this->quotedTable = $this->dialect->quote($table, 'name');
        return $this;
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
     * @return SQLBlobStorage
     */
    public function setIdKey($idKey)
    {
        $this->idKey = $idKey;
        $this->quotedIdKey = $this->dialect->quote($idKey, 'name');
        return $this;
    }

    /**
     * @return string
     */
    public function getBlobKey()
    {
        return $this->blobKey;
    }

    /**
     * @param string $blobKey
     * @return SQLBlobStorage
     */
    public function setBlobKey($blobKey)
    {
        $this->blobKey = $blobKey;
        $this->quotedBlobKey = $this->dialect->quote($blobKey, 'name');
        return $this;
    }

    /**
     * This storage typically holds data for multiple use cases split them
     * be a discriminator to allow multiple storages per table and avoid clashes
     * between them.
     *
     * @return string
     */
    public function getDiscriminator()
    {
        return $this->discriminator;
    }

    /**
     * @param string $discriminator
     *
     * @return SQLBlobStorage
     *
     * @see self::getDiscriminator()
     */
    public function setDiscriminator($discriminator)
    {
        $this->discriminator = $discriminator;
        return $this;
    }

    /**
     * @return string
     */
    public function getDiscriminatorKey()
    {
        return $this->discriminatorKey;
    }

    /**
     * @param string $discriminatorKey
     * @return SQLBlobStorage
     */
    public function setDiscriminatorKey($discriminatorKey)
    {
        $this->discriminatorKey = $discriminatorKey;
        return $this;
    }

    /**
     * @return Serializer
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param Serializer $serializer
     * @return SQLBlobStorage
     */
    public function setSerializer(Serializer $serializer)
    {
        $this->serializer = $serializer;
        return $this;
    }


    /**
     * @return string
     */
    protected function discriminator()
    {
        if ($this->discriminator === null) {
            throw new UnConfiguredException("For safety reason you have to set a discriminator, if you dont use it set it to an empty string.");
        }
        return $this->discriminator;
    }

    /**
     * Add the discriminator if it was set
     *
     * @param $columns
     */
    protected function restrictIfNeeded(&$columns)
    {
        if ($discriminator = $this->discriminator()) {
            $columns[$this->discriminatorKey] = $discriminator;
        }
    }

    /**
     * Build the where array that can be used by SQL::f()
     *
     * @param int|string $offset
     * @return array
     */
    protected function buildWhere($offset)
    {
        $where = [$this->idKey => $offset];
        $this->restrictIfNeeded($where);
        return $where;
    }
}
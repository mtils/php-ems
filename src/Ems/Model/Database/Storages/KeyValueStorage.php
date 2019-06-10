<?php
/**
 *  * Created by mtils on 08.06.19 at 07:36.
 **/

namespace Ems\Model\Database\Storages;


use Ems\Contracts\Core\Serializer;
use Ems\Contracts\Core\Storage;
use Ems\Contracts\Model\Database\Connection;
use Ems\Core\Collections\OrderedList;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Serializer\JsonSerializer;
use Ems\Model\Database\SQL;

class KeyValueStorage implements Storage
{
    use DatabaseStorageTrait;

    /**
     * @var Serializer
     */
    protected $serializer;

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
     * KeyValueStorage constructor.
     *
     * @param Connection $connection
     * @param string $table
     * @param string $blobKey
     */
    public function __construct(Connection $connection, $table = 'blob_entries', $blobKey='data')
    {
        $this->connection = $connection;
        $this->setTable($table);
        $this->setBlobKey($blobKey);
        $this->setIdKey($this->idKey); // Just to set the quotedBlobKey
        $this->serializer = (new JsonSerializer())->asArrayByDefault();
    }

    /**
     * {@inheritDoc}
     *
     * @return boolean true on success or false on failure.
     **/
    public function offsetExists($offset)
    {
        return $this->getDriver()->exists($offset);
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
        if (!$row = $this->getDriver()->selectOne($offset)) {
            throw new KeyNotFoundException("Entry #$offset not found");
        }
        return $this->serializer->deserialize($row[$this->blobKey]);
    }

    /**
     * {@inheritDoc}
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

        $this->getDriver()->replace([
            $this->idKey => $offset,
            $this->blobKey => $value
        ]);

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
        $this->getDriver()->delete($offset, 1);
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
        $this->getDriver()->delete($keys === null ? '*' : $keys);
        return true;
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
        return new OrderedList($this->getDriver()->keys());

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
     * @return string
     */
    public function getBlobKey()
    {
        return $this->blobKey;
    }

    /**
     * @param string $blobKey
     *
     * @return $this
     */
    public function setBlobKey($blobKey)
    {
        $this->blobKey = $blobKey;
        $this->quotedBlobKey = $this->quote($blobKey, 'name');
        $this->reconfigureDriverIfExists();
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
     * @return $this
     *
     * @see self::getDiscriminator()
     */
    public function setDiscriminator($discriminator)
    {
        $this->discriminator = $discriminator;
        $this->reconfigureDriverIfExists();
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
     *
     * @return $this
     */
    public function setDiscriminatorKey($discriminatorKey)
    {
        $this->discriminatorKey = $discriminatorKey;
        $this->reconfigureDriverIfExists();
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
     *
     * @return $this
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
     * Overwrite this method to return the "column" part of the select one
     * query
     *
     * @return string
     */
    protected function getSelectColumnString()
    {
        return $this->dialect()->quote($this->getBlobKey(), 'name');
    }


    protected function configureStorageDriver(StorageDriver $driver)
    {
        $driver->configure(
            $this->table,
            $this->idKey,
            $this->getSelectColumnString(),
            $this->discriminatorKey,
            $this->discriminator()
        );
    }


}
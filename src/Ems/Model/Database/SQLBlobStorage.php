<?php
/**
 *  * Created by mtils on 22.05.19 at 11:00.
 **/

namespace Ems\Model\Database;


use Ems\Contracts\Core\HasKeys;
use Ems\Contracts\Core\PushableStorage;
use Ems\Model\Database\Storages\KeyValueStorage;

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
class SQLBlobStorage extends KeyValueStorage implements PushableStorage, HasKeys
{

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
        return $this->getDriver()->insert([$this->blobKey => $encodedValue]);
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
        $this->getDriver()->update($offset, [$this->blobKey=>$value], 1);
    }


}
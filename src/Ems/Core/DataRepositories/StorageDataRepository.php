<?php

/**
 *  * Created by mtils on 02.04.20 at 19:56.
 **/

namespace Ems\Core\DataRepositories;

use Ems\Contracts\Core\DataRepository;
use Ems\Contracts\Core\PushableStorage;
use LogicException;

/**
 * Class StorageDataRepository
 *
 * @package Ems\Core\DataRepositories
 */
class StorageDataRepository implements DataRepository
{
    /**
     * @var PushableStorage
     */
    private $storage;

    /**
     * @var string
     */
    private $idKey = 'id';

    /**
     * Return the data for $id. Data usually includes the id.
     *
     * @param int|string $id
     *
     * @return array
     */
    public function get($id)
    {
        return $this->storage->offsetGet($id);
    }

    /**
     * Create a record. Return the created data (that includes often generated
     * data or did manipulate the passed data).
     *
     * @param array $data
     *
     * @return array
     */
    public function create(array $data)
    {
        $id = $this->storage->offsetPush($data);
        $data[$this->idKey] = $id;
        return $data;
    }

    /**
     * Update data. Return the updated data.
     *
     * @param array $data
     *
     * @return array
     *
     * @throws LogicException
     */
    public function update(array $data)
    {
        if (!isset($data[$this->idKey])) {
            throw new LogicException("Missing $this->idKey key to extract the id,");
        }

        $this->storage[$this->idKey] = $data;
        return $this->storage[$this->idKey];
    }

    /**
     * Delete $data that is stored under $id.
     *
     * @param int|string $id
     *
     * @return bool
     */
    public function delete($id)
    {
        $this->storage->offsetUnset($id);
        return true;
    }
}

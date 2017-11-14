<?php

namespace Ems\Contracts\Core;

use ArrayAccess;

/**
 * An buffered storage has separate persisting methods. The user if this class
 * will know that he has to use some separate method calls to persist the data.
 **/
interface BufferedStorage extends Storage
{
    /**
     * Persists all data, whatever the storage uses to store the data
     * If the whole data is stored as json the Storage would save
     * the json file here.
     *
     * @return bool (if successfull)
     **/
    public function persist();

    /**
     * Clears all data if no keys passed. If keys passed remove alle the passed
     * keys. (More or less foreach ($keys as $key) { self::unset($key); }).
     * The keys are a performance related feature of storages. In some cases the
     * storage knows a much faster way to remove multiple entries at once.
     * The keys are by default null, so it cannot happen to unintendedly delete
     * all data by an empty array.
     * Please implement it this way : if ($keys === null) { deleteAll() }
     *
     * @param array $keys (optional)
     *
     * @return bool (if successfull)
     **/
    public function purge(array $keys=null);
}

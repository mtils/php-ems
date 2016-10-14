<?php

namespace Ems\Contracts\Core;

use ArrayAccess;

/**
 * A storage is the simplest form of data storage.
 * (Simpler than for example Repository)
 * It just needs to persists itself and is used like an array. In combination
 * with the has_array_access() and force_array_access() function
 * you can accept array and ArrayAccess in your classes
 *
 * You can deceide yourself if the storage always stores if a value is changed
 * or just on persist(). That is mostly a performance concern.
 *
 * ArrayAccess:
 * @method     Storage offsetExists($offset) Check if entry with key $offset exists
 * @method     Storage offsetGet($offset) Return the stored data for key $offset
 * @method     Storage offsetSet($offset, $value) Set data under key $offset. No objects are allowed
 * @method     Storage offsetUnset($offset) Remove the data under key $offset
 **/
interface Storage extends ArrayAccess
{
    /**
     * Persists all data, whatever the storage uses to store the data
     * If the whole data is stored as json the Storage would save
     * the json file here
     *
     * @return bool (if successfull)
     **/
    public function persist();

    /**
     * Clears all data, whatever the storage uses to store the data
     *
     * @return bool (if successfull)
     **/
    public function purge();
}

<?php

namespace Ems\Contracts\Core;

/**
 * A storage is the simplest form of data storage.
 * (Simpler than for example Repository)
 * It just needs to persists itself and is used like an array. In combination
 * with the has_array_access() and force_array_access() function
 * you can accept array and ArrayAccess in your classes.
 *
 * Decide by the backend if an unbuffered or buffered storage is better and mark
 * it with isBuffered() true/false
 *
 * ArrayAccess:
 *
 * @see     ArrayAccess::offsetExists($offset) Check if entry with key $offset exists
 * @see     ArrayAccess::offsetGet(mixed $offset) Return the stored data for key $offset
 * @see     ArrayAccess::offsetSet(mixed $offset, mixed $value) Set data under key $offset. No objects are allowed
 * @see     ArrayAccess::offsetUnset(mixed $offset) Remove the data under key $offset
 **/
interface Storage extends ArrayData
{

    /**
     * Marks the storage as a filesystem storage.
     *
     * @var string
     **/
    const FILESYSTEM = 'filesystem';

    /**
     * Marks the storage as a sql database storage.
     *
     * @var string
     **/
    const SQL = 'sql';

    /**
     * Marks the storage as a nosql storage.
     *
     * @var string
     **/
    const NOSQL = 'nosql';

    /**
     * Marks the storage as a in-memory storage.
     *
     * @var string
     **/
    const MEMORY = 'memory';

    /**
     * Marks the storage as a storage using a webservice/api.
     *
     * @var string
     **/
    const WEBSERVICE = 'webservice';

    /**
     * Marks the storage as a utility storage (like a proxy for other storages).
     *
     * @var string
     **/
    const UTILITY = 'utility';


    /**
     * Returns a hint what this storage is (filesystem, sql, webservice...)
     *
     * @return string
     *
     * @see self::FILESYSTEM
     * @see self::SQL
     * @see self::NOSQL
     **/
    public function storageType();

    /**
     * A buffered storage needs an manual call to persist() to write the state.
     * An unbuffered storage just writes on every change.
     *
     * @return bool
     */
    public function isBuffered();

    /**
     * Persists all data, whatever the storage uses to store the data
     * If the whole data is stored as json the Storage would save
     * the json file here.
     *
     * A call to persist on an unbuffered storage returns always true
     *
     * @return bool (if successful)
     **/
    public function persist();

}

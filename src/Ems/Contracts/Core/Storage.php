<?php

namespace Ems\Contracts\Core;

use ArrayAccess;

/**
 * A storage is the simplest form of data storage.
 * (Simpler than for example Repository)
 * It just needs to persists itself and is used like an array. In combination
 * with the has_array_access() and force_array_access() function
 * you can accept array and ArrayAccess in your classes.
 *
 * This is like an abstract interface. Your Storage should either implement
 * UnbufferedStorage or BufferedStorage to mark it the right way.
 * You can, by the way, use proxy storages to make any storage
 * buffered or unbuffered.
 *
 * ArrayAccess:
 *
 * @method     Storage offsetExists($offset) Check if entry with key $offset exists
 * @method     Storage offsetGet($offset) Return the stored data for key $offset
 * @method     Storage offsetSet($offset, $value) Set data under key $offset. No objects are allowed
 * @method     Storage offsetUnset($offset) Remove the data under key $offset
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


}

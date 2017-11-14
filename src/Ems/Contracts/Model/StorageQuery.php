<?php

namespace Ems\Contracts\Model;

use Ems\Contracts\Expression\ConditionGroup;

/**
 * A StorageQuery is an object which is returned by a queryable storage.
 * in where(). You can select entries of the storage by a a storageFilter.
 * You could also purge specific entries by $storage->where('id', [3,4,5])->purge()
 **/
interface StorageQuery extends Result, ConditionGroup
{
    /*
     * Delete all entries of this result
     *
     * @return bool (if successfull)
     **/
    public function purge();
}

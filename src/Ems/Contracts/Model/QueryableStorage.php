<?php

namespace Ems\Contracts\Model;

use Ems\Contracts\Core\Storage;
use Ems\Contracts\Expression\Queryable;

/**
 * A QueryableStorage is a storage which can be queried:
 *
 * @example Storage::where() returns QueryableResult
 **/
interface QueryableStorage extends Storage, Queryable
{
    //
}

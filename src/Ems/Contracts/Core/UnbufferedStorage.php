<?php

namespace Ems\Contracts\Core;

use ArrayAccess;

/**
 * An unbuffered storage just passes the values thru to the persistence.
 * If a Storage is unbuffered, the user of this class will not have to
 * call anything to persist the data
 **/
interface UnbufferedStorage extends Storage
{
    //
}

<?php


namespace Ems\Contracts\Core;

use ArrayAccess;

/**
 * This is a container for any data. Return all known or
 * possible keys this container holds.
 **/
interface ArrayWithState extends ArrayData, ChangeTracking
{
    //
}

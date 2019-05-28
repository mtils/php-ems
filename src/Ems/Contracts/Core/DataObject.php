<?php
/**
 *  * Created by mtils on 27.05.19 at 14:22.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface DataObject
 *
 * DataObject defines the basic interface to work as a data container for
 * Repositories. It must be by filled by an array and be casted to an array and
 * needs an (unique) id.
 *
 * @package Ems\Contracts\Core
 */
interface DataObject extends Identifiable, Arrayable, Hydratable
{
    /**
     * Return if this object is from storage (exists) or was just instantiated.
     *
     * @return bool
     */
    public function isNew();
}
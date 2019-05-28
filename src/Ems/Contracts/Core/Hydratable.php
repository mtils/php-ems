<?php
/**
 *  * Created by mtils on 27.05.19 at 13:56.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface Hydratable
 *
 * This marks objects that can be filled by an array. This is mainly used for
 * orm or data objects which have to be filled by a repository.
 *
 * @package Ems\Contracts\Core
 */
interface Hydratable
{
    /**
     * Hydrate the object by $data. Optionally pass an id. If an id was passed
     * the object assumes to be hydrated from storage. If the passed id === null
     * it assumes it is new.
     * If you want to overwrite the "from storage or not"-behaviour pass a boolean
     * third $forceFromStorage attribute.
     *
     * Hydrate means: Completely clear the object and refill it.
     *
     * @param array      $data
     * @param int|string $id (optional)
     * @param bool       $forceIsFromStorage (optional)
     *
     * @return void
     */
    public function hydrate(array $data, $id=null, $forceIsFromStorage=null);

    /**
     * Fill the object with the passed data. Do not clear the object before you
     * fill it.
     *
     * @param array $data
     *
     * @return self
     */
    public function apply(array $data);
}
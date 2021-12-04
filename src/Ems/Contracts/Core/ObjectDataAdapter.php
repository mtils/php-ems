<?php
/**
 *  * Created by mtils on 14.09.19 at 09:38.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface ObjectDataAdapter
 *
 * The ObjectDataAdapter sits between an central repository and the data objects.
 * So instead of forcing your objects to have any interface how to get/set data
 * (ArrayAccess, __get/__set, getter/setter, ...) EMS works with an adapter.
 * So you write this adapter once and every EMS Repository can work with your
 * objects,
 *
 * @package Ems\Contracts\Core
 */
interface ObjectDataAdapter extends ObjectArrayConverter
{
    /**
     * Fill the passed object with tha $data. If you mark your object as new or
     * something like this you can use $isFromStorage.
     *
     * @param object            $object
     * @param array             $data
     * @param string|int        $id (optional)
     * @param $isFromStorage    (default:false)
     *
     * @return object
     */
    public function fill($object, array $data, $id=null, bool $isFromStorage=false);

    /**
     * Return true if the passed object is new. (not from storage)
     *
     * @param object $object
     *
     * @return bool
     */
    public function isNew($object) : bool;

    /**
     * Return the id of the object. If you have a composite id compose it.
     *
     * @param object $object
     *
     * @return string|int
     */
    public function id($object);
}
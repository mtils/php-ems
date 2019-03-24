<?php
/**
 *  * Created by mtils on 08.09.18 at 14:38.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface PushableStorage
 *
 * The PushableStorage interface is for creating entries in a storage without
 * a key.
 * So if you have a sql storage and the table has an autoincrement ID you could
 * add data to the storage without a $key.
 * Due to the fact that Storage is basically only ArrayAccess the method to add
 * data to the storage is like $array[] = $data.
 * Its name is offsetPush($value).
 *
 * @package Ems\Contracts\Core
 */
interface PushableStorage extends Storage
{
    /**
     * Add Data
     *
     * @param mixed $value
     *
     * @return string|int
     */
    public function offsetPush($value);
}
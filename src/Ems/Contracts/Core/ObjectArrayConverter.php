<?php
/**
 *  * Created by mtils on 14.09.19 at 09:22.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface ObjectArrayConverter
 *
 * An ObjectArrayConverter converts Arrays to Objects and reverse.
 *
 * @package Ems\Contracts\Core
 */
interface ObjectArrayConverter
{
    /**
     * Turn an object into an array. If depth
     *
     * @param object $object
     * @param int $depth (default:0)
     * @return array
     */
    public function toArray($object, $depth=0);

    /**
     * @param array $data
     * @param bool $isFromStorage (default:false)
     *
     * @return object
     */
    public function fromArray(array $data=[], $isFromStorage=false);
}
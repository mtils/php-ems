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
     * Create an object of $classOrInterface by the passed array.
     * Mark it as "new" or "from storage" by the third parameter.
     *
     * @param string    $classOrInterface
     * @param array     $data (optional)
     * @param bool      $isFromStorage (default:false)
     *
     * @return object
     */
    public function fromArray(string $classOrInterface, array $data=[], $isFromStorage=false);
}
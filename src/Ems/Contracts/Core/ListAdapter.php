<?php
/**
 *  * Created by mtils on 27.09.20 at 08:25.
 **/

namespace Ems\Contracts\Core;

use Traversable;

/**
 * Interface ListAdapter
 *
 * A ListAdapter is used to allow your custom lists/collections/arrays/sequences.
 *
 * @package Ems\Contracts\Core
 */
interface ListAdapter
{
    /**
     * Create a new list.
     *
     * @param string $classOrInterface
     * @param string $path
     *
     * @return Traversable|array
     */
    public function newList(string $classOrInterface, string $path);

    /**
     * Add an item to the list.
     *
     * @param string            $classOrInterface
     * @param string            $path
     * @param Traversable|array $list
     * @param mixed             $item
     *
     * @return void
     */
    public function addToList(string $classOrInterface, string $path, &$list, &$item);

    /**
     * Remove an item from the list.
     *
     * @param string            $classOrInterface
     * @param string            $path
     * @param Traversable|array $list
     * @param mixed             $item
     *
     * @return void
     */
    public function removeFromList(string $classOrInterface, string $path, &$list, &$item);

}
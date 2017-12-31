<?php
/**
 *  * Created by mtils on 17.12.17 at 10:56.
 **/

namespace Ems\Contracts\Core;

use Traversable;

class Map
{
    /**
     * Return true if $check returns true on ALL items.
     *
     * @param array|Traversable $items
     * @param callable          $check
     *
     * @return bool
     */
    public static function all($items, callable $check)
    {
        foreach ($items as $item) {
            if (!call_user_func($check, $item)) {
                return false;
            }
        }

        // Return false if array is empty
        return isset($item) ? true : false;
    }

    /**
     * Return true if $check returns true on ANY item.
     *
     * @param array|Traversable $items
     * @param callable          $check
     *
     * @return bool
     */
    public static function any($items, callable $check)
    {
        foreach ($items as $item) {
            if (call_user_func($check, $item)) {
                return true;
            }
        }
        return false;
    }
}
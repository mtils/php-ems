<?php
/**
 *  * Created by mtils on 17.12.17 at 10:56.
 **/

namespace Ems\Contracts\Core;

use Traversable;
use function call_user_func;

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

    /**
     * Take every item $listOfArguments and call $callable with it.
     *
     * @param array $listOfArguments
     * @param callable $callable
     *
     * @return array (all results)
     *
     * @example $listOfArguments = [
     *     [$user1, true]
     *     [$user2, false]
     *     [$user3, true]
     * ];
     *
     * Map::apply($listOfArguments, [$registrar, 'activate']);
     *
     * interface Registrar {
     *     function activate(User $user, $sendMail=false);
     * }
     *
     */
    public static function apply(array $listOfArguments, callable $callable)
    {
        $results = [];

        foreach ($listOfArguments as $arguments) {
            $results[] = call_user_func($callable, ...(array)$arguments);
        }

        return $results;
    }

    /**
     * This is like array_map but faster, works with \Traversable.
     * It throws all results away.
     *
     * @param callable[] $items
     *
     * @param mixed|array $args (optional)
     */
    public static function callVoid($items, $args=null)
    {
        foreach ($items as $callable) {
            call_user_func($callable, ...(array)$args);
        }
    }

    /**
     * Return the first value of a key in $items.
     * This means iterate over items, get the first and return the first value
     * of this item. This means that the item has to be \Traversable.
     *
     * @param array|Traversable $items
     *
     * @return mixed
     */
    public static function firstItemValue($items)
    {
        Type::force($items, Traversable::class);

        foreach ($items as $item) {
            Type::force($item, Traversable::class);
            foreach ($item as $key=>$value) {
                return $value;
            }
        }
        return null;
    }

}
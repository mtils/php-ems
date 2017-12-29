<?php
/**
 *  * Created by mtils on 26.12.17 at 07:27.
 **/

namespace Ems\Contracts\Model;

/**
 * Interface CanSort
 *
 * An CanSort object is able to sort results. A query object or a search a the
 * right candidates to implement CanSort.
 *
 * @package Ems\Contracts\Model
 */
interface CanSort
{

    /**
     * @var string
     */
    const ASC = 'asc';

    /**
     * @var string
     */
    const DESC = 'desc';

    /**
     * Sort by $key in $direction. Or pass an array to set many keys.
     *
     * @param string|array $key
     * @param string $direction (default: 'asc')
     *
     * @return $this
     */
    public function sort($key, $direction=self::ASC);

    /**
     * Reset the sorting. Pass a key to remove the sorting by this key.
     *
     * @param string $key (optional)
     *
     * @return $this
     */
    public function clearSort($key=null);

    /**
     * Return an array of $key=>$direction items int the order it will be
     * performed.
     * Pass a key to get the sorting direction of a special key. If it is not
     * sorted by the passed key, return null.
     *
     * @param string $key (optional)
     *
     * @return array|string|null
     */
    public function sorting($key=null);

    /**
     * Check if this object should sort by $key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isSortedBy($key);

    /**
     * Return the sorting priority of key. 1 is the highest priority, 0 means
     * it is not sorted by that key. Every other number is the position within
     * its array. (ORDER BY id DESC (1), updated_at DESC (2), name ASC (3))
     *
     * @param string $key
     *
     * @return int
     */
    public function sortingPriority($key);

}
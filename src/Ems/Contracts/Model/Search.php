<?php
/**
 *  * Created by mtils on 26.12.17 at 07:41.
 **/

namespace Ems\Contracts\Model;


use Ems\Contracts\Core\HasKeys;
use Ems\Core\Collections\StringList;

/**
 * Interface Search
 *
 * The Search interface is a container for search operations. It is a by design
 * reduced version of an Expression without operators, multiple expressions per
 * key...
 * Create a search object in your controller, pass it to the view and iterate
 * over the search in your view. Make it a PaginatableResult to allow pagination.
 *
 * The search will be performed once, no matter how often you iterate over its
 * results.
 * So in opposite to Expression the Search is mutable.
 *
 * The HasKeys interface is to return all RESULT keys.
 *
 * The proposed usage of a search is like this:
 *
 * 1. Do some validation and casting of the input
 * 2. instantiate a Search (with a backend in __construct)
 * 3. Call apply($input)
 * 4. Pass the search to the view
 * 5. Iterate or paginate over the search
 * 5. Then an expression will be created by the search
 * 6. The backend will be called by Search with the created Expression.
 * 7. The backend will return the results (to the Search)
 *
 * But the whole backend thing is an implementation detail of your Search. Don't
 * leak it to the outside of that interface.
 *
 * @package Ems\Contracts\Model
 */
interface Search extends Result, HasKeys
{

    /**
     * Apply the passed criteria to the search. The parameters are cleared before
     * the new ones will by applied.
     *
     * @param array $input
     *
     * @return $this
     */
    public function apply(array $input);

    /**
     * Get what you applied. Pass nothing to get all, a key to get one value.
     * Pass a default value by the second parameter if the key was not passed.
     *
     * @param string $key (optional)
     * @param mixed $default (optional)
     *
     * @return mixed
     */
    public function input($key=null, $default=null);

    /**
     * Add a filter manually. Pass an array to add multiple values. The input is
     * not cleared when calling this method.
     *
     * @param string|array $key
     * @param mixed        $value (optional)
     *
     * @return $this
     */
    public function filter($key, $value=null);

    /**
     * Get the filter value that was passed by apply.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function filterValue($key);

    /**
     * Check if a filter for $key was set. Pass a value to check that it was set
     * and the value was passed for this filter.
     *
     * @param string $key
     * @param mixed  $value (optional)
     *
     * @return bool
     */
    public function hasFilter($key, $value=null);

    /**
     * Remove the filter with $key or all if no key was passed.
     *
     * @param string $key (optional)
     *
     * @return $this
     */
    public function clearFilter($key=null);

    /**
     * Return all applied filter keys.
     *
     * @return StringList|string[]
     */
    public function filterKeys();
}
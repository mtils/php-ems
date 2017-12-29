<?php
/**
 *  * Created by mtils on 26.12.17 at 08:12.
 **/

namespace Ems\Contracts\Model;


use Ems\Contracts\Expression\ConditionGroup;

/**
 * Interface SearchEngine
 *
 * A search engine supports the search inside an backend. If you don't like to
 * have separate Search Engine objects, make your repository a SearchEngine.
 *
 * The SearchEngine is primary used by Search to create the condition and then
 * to retrieve the results.
 *
 * @package Ems\Contracts\Model
 */
interface SearchEngine
{
    /**
     * Create a new Query. (With the constraints that guaranty supported
     * usage)
     *
     * @return ConditionGroup
     */
    public function newQuery();

    /**
     * Perform a search with the filter $conditions. Return (at least) $keys and
     * sort the result by $sorting ($key=>$direction).
     *
     * @param ConditionGroup $conditions
     * @param array          $sorting (optional)
     * @param string[]       $keys (optional)
     *
     * @return Result
     */
    public function search(ConditionGroup $conditions, array $sorting = [], $keys = []);
}
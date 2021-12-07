<?php
/**
 *  * Created by mtils on 06.01.18 at 06:21.
 **/

namespace Ems\Contracts\Pagination;


use Countable;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Model\Result;
use Traversable;

/**
 * Interface Paginator
 *
 * A Paginator is used to paginate a result.
 * The IteratorAggregate interface is used to return the result items
 * not the pages. The Countable interface returns the count of
 * limited results. So with a totalCount of 1000 and 10 per page it
 * would return 10 (or less on the last page).
 *
 * @package Ems\Contracts\Pagination
 */
interface Paginator extends Result, Countable
{
    /**
     * Return all pages. By default, it returns a squeezed list of pages for "..."
     * buttons somewhere in the middle. To really get all pages pass 0.
     * Without a total count this is not used.
     *
     * @param int|null $squeezeTo (optional)
     *
     * @return Pages
     */
    public function pages(int $squeezeTo=null) : Pages;

    /**
     * Get the number of the current page.
     *
     * @return int
     */
    public function getCurrentPageNumber() : int;

    /**
     * Get the number of items per page.
     *
     * @return int
     */
    public function getPerPage() : int;

    /**
     * Set current page and perPage. The per page is optional and has to
     * be set to a default value by the paginator itself.
     *
     * @param int $currentPage
     * @param int|null $perPage (optional)
     *
     * @return $this
     */
    public function setPagination(int $currentPage, int $perPage=null) : Paginator;

    /**
     * Set the already limited database result. Pass the $totalCount to make
     * this a "length aware" paginator. Passing a callable will not trigger any
     * further queries until you really need pages. This way you can use a
     * paginator as a chunked result set without the cost of an additional query.
     *
     * @param array|Traversable $items
     * @param int|callable|null $totalCount (optional)
     *
     * @return $this
     */
    public function setResult($items, $totalCount=null) : Paginator;

    /**
     * If you have no total count, but you know there are previous and next
     * pages, set the result by this method.
     *
     * @param array|Traversable $items
     * @param bool $hasPreviousPage
     * @param bool $hasNextPage
     *
     * @return $this
     */
    public function setResultAndDirections($items, bool $hasPreviousPage, bool $hasNextPage) : Paginator;

    /**
     * Return true if this paginator was constructed with a total count.
     * Without a total count it is not length aware and can just
     * know if it is on the first page.
     * This method also returns true if you pass a callable in setResult.
     *
     * @return bool
     */
    public function hasTotalCount() : bool;

    /**
     * Get the passed totalCount or call the totalCount callable and return its
     * result.
     *
     * @return int|null
     */
    public function getTotalCount();

    /**
     * Return the base url. With this url the page urls will be built.
     *
     * @return Url
     */
    public function getBaseUrl() : Url;

    /**
     * Set the base url.
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setBaseUrl(Url $url) : Paginator;

    /**
     * Get the (GET) parameter name for applying the page.
     *
     * @return string
     */
    public function getPageParameterName() : string;

    /**
     * Set the (GET) parameter name for applying the page.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setPageParameterName(string $name) : Paginator;

    /**
     * Return the offset for a database query (or array_slice) matching the
     * currentPageNumber and perPage.
     *
     * @return int
     */
    public function getOffset() : int;

    /**
     * Slice a complete result into the desired page/perPage.
     *
     * @param array|Traversable $completeResult
     *
     * @return array
     */
    public function slice($completeResult) : array;
}
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
     * Return all pages. By default it returns a squeezed list of pages for "..."
     * buttons somewhere in the middle. To really get all pages pass 0.
     * Without a total count this is not used.
     *
     * @param int $squeezeTo (optional)
     *
     * @return Pages
     */
    public function pages($squeezeTo=null);

    /**
     * Get the number of the current page.
     *
     * @return int
     */
    public function getCurrentPageNumber();

    /**
     * Get the number of items per page.
     *
     * @return int
     */
    public function getPerPage();

    /**
     * Set current page and perPage. The per page is optional and has to
     * be set to a default value.
     *
     * @param int $currentPage
     * @param int $perPage (optional)
     *
     * @return $this
     */
    public function setPagination($currentPage, $perPage=null);

    /**
     * Set the already limited database result.
     *
     * @param array|\Traversable $items
     * @param int $totalCount (optional)
     *
     * @return $this
     */
    public function setResult($items, $totalCount=null);

    /**
     * Return if this paginator was constructed with a total count.
     * Without a total count it is not length aware and can just
     * know if it is on the first page.
     *
     * @return bool
     */
    public function hasTotalCount();

    /**
     * @return int
     */
    public function getTotalCount();

    /**
     * Return the base url. With this url the page urls will be built.
     *
     * @return Url
     */
    public function getBaseUrl();

    /**
     * Set the base url.
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setBaseUrl(Url $url);

    /**
     * Get the (GET) parameter name for applying the page.
     *
     * @return string
     */
    public function getPageParameterName();

    /**
     * Set the (GET) parameter name for applying the page.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setPageParameterName($name);

    /**
     * Return the offset for a database query (or array_slice) matching the
     * currentPageNumber and perPage.
     *
     * @return int
     */
    public function getOffset();

    /**
     * Slice a complete result into the desired page/perPage.
     *
     * @param array|Traversable $completeResult
     *
     * @return array
     */
    public function slice($completeResult);
}
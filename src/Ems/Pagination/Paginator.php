<?php
/**
 *  * Created by mtils on 06.01.18 at 06:41.
 **/

namespace Ems\Pagination;

use ArrayIterator;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Pagination\Page;
use Ems\Contracts\Pagination\Pages;
use Ems\Contracts\Pagination\Paginator as PaginatorContract;
use Ems\Model\ResultTrait;
use function filter_var;
use function is_numeric;
use Traversable;
use function array_slice;

class Paginator implements PaginatorContract
{
    use ResultTrait;

    /**
     * @var Pages
     */
    protected $pages;

    /**
     * @var int
     */
    protected $currentPageNumber = 1;

    /**
     * @var int
     */
    protected $perPage;

    /**
     * @var int|null
     */
    protected $totalCount;

    /**
     * @var UrlContract
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $pageParameterName;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var int
     */
    protected static $perPageDefault = 15;

    /**
     * @var string
     */
    protected static $defaultPageParameterName = 'page';

    /**
     * @var int
     */
    protected static $defaultSqueeze = 10;

    /**
     * @var int
     */
    protected static $defaultSqueezeSpace = 2;

    /**
     * Paginator constructor.
     *
     * @param int $currentPage (optional)
     * @param int $perPage (optional)
     * @param object $creator (optional)
     */
    public function __construct($currentPage=1, $perPage=null, $creator=null)
    {
        $this->setPagination($currentPage, $perPage);
        $this->pageParameterName = static::$defaultPageParameterName;
        $this->_creator = $creator;
    }

    /**
     * {@inheritdoc}
     *
     * @return Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $squeezeTo (optional)
     *
     * @return Pages
     */
    public function pages($squeezeTo=null)
    {
        if (!$this->pages) {
            $this->pages = $this->buildPages($squeezeTo === null ? static::$defaultSqueeze : $squeezeTo);
        }
        return $this->pages;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function getCurrentPageNumber()
    {
        return $this->currentPageNumber;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $currentPage
     * @param int $perPage (optional)
     *
     * @return PaginatorContract
     */
    public function setPagination($currentPage, $perPage = null)
    {

        $this->currentPageNumber = $this->isValidPageNumber($currentPage) ? $currentPage : 1;
        $defaultPerPage = $this->perPage ? $this->perPage : static::$perPageDefault;
        $this->perPage = $perPage ?: $defaultPerPage;
        $this->pages = null;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array|\Traversable $items
     * @param int $totalCount (optional)
     *
     * @return $this
     */
    public function setResult($items, $totalCount = null)
    {
        $this->items = Type::toArray($items);
        $this->totalCount = $totalCount;
        $this->pages = null;
        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function hasTotalCount()
    {
        return $this->totalCount !== null;
    }

    /**
     * {@inheritdoc}
     *
     * @return UrlContract
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * {@inheritdoc}
     *
     * @param UrlContract $url
     *
     * @return PaginatorContract
     */
    public function setBaseUrl(UrlContract $url)
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * Get the (GET) parameter name for applying the page.
     *
     * @return string
     */
    public function getPageParameterName()
    {
        return $this->pageParameterName;
    }

    /**
     * Set the (GET) parameter name for applying the page.
     *
     * @param string $name
     *
     * @return PaginatorContract
     */
    public function setPageParameterName($name)
    {
        $this->pageParameterName = $name;
        return $this;
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Return the offset for a database query (or array_slice) matching the
     * currentPageNumber and perPage.
     *
     * @return int
     */
    public function getOffset()
    {
        return ($this->currentPageNumber-1)*$this->perPage;
    }

    /**
     * Slice a complete result into the desired page/perPage.
     *
     * @param array|Traversable $completeResult
     *
     * @return array
     */
    public function slice($completeResult)
    {
        $all = Type::toArray($completeResult);
        return array_slice($all, $this->getOffset(), $this->perPage);
    }


    /**
     * Get the default perPage value.
     *
     * @return int
     */
    public static function getPerPageDefault()
    {
        return static::$perPageDefault;
    }

    /**
     * Set the default perPage value.
     *
     * @param int $perPage
     */
    public static function setPerPageDefault($perPage)
    {
        static::$perPageDefault = $perPage;
    }

    /**
     * Return the default page parameter name.
     *
     * @return string
     */
    public static function getDefaultPageParameterName()
    {
        return static::$defaultPageParameterName;
    }

    /**
     * Set the default page parameter name.
     *
     * @param string $name
     */
    public static function setDefaultPageParameterName($name)
    {
        static::$defaultPageParameterName = $name;
    }

    /**
     * Get the default number of maximum rendered pages.
     *
     * @return int
     */
    public static function getDefaultSqueeze()
    {
        return static::$defaultSqueeze;
    }

    /**
     * Set the amount of maximum pages. Set it to null to return non squeezed
     * results by default.
     *
     * @param int $squeeze
     */
    public static function setDefaultSqueeze($squeeze)
    {
        static::$defaultSqueeze = $squeeze;
    }

    /**
     * Get the default space between the placeholder (...) page and its closest
     * end.
     *
     * @return int
     */
    public static function getDefaultSqueezeSpace()
    {
        return static::$defaultSqueezeSpace;
    }

    /**
     * Get the default space between the placeholder (...) page and its closest
     * end.
     *
     * @param int $space
     */
    public static function setDefaultSqueezeSpace($space)
    {
        static::$defaultSqueezeSpace = $space;
    }

    /**
     * @param int $squeezeTo
     *
     * @return Pages
     */
    protected function buildPages($squeezeTo)
    {
        if (!$this->hasTotalCount()) {
            return $this->buildLengthUnaware();

        }

        $numberOfPages = $this->totalCount ? (int)(floor($this->totalCount/$this->perPage)+1) : 0;

        if ($squeezeTo && $numberOfPages > $squeezeTo) {
            return $this->buildSqueezed($squeezeTo, $numberOfPages);
        }

        return $this->buildLengthAware($numberOfPages);
    }

    /**
     * Buid pages if total count is known.
     *
     * @param int $numberOfPages
     *
     * @return Pages
     */
    protected function buildLengthAware($numberOfPages)
    {
        $pages = $this->newPages();

        if ($this->totalCount < 1) {
            return $pages;
        }

        return $this->addPages($pages, 1, $numberOfPages);

    }

    /**
     * Build pages when no total count is available.
     *
     * @return Pages
     */
    protected function buildLengthUnaware()
    {
        $pages = $this->newPages();

        $itemCount = count($this->items);

        if ($itemCount < 1) {
            return $pages;
        }

        $perhapsMorePages = $itemCount >= $this->perPage;
        $pageParameter = $this->getPageParameterName();
        $currentPage = $this->currentPageNumber;
        $baseUrl = $this->baseUrl;

        for ($page=1; $page <= ($currentPage+1); $page++) {

            if (!$perhapsMorePages && $page > $currentPage) {
                break;
            }

            $pages->add($this->newPage([
                'number'      => $page,
                'url'         => $baseUrl ? $baseUrl->query($pageParameter, $page) : '',
                'is_current'  => $page == $currentPage,
                'is_previous' => $page == $currentPage-1,
                'is_next'     => $page == $currentPage+1,
                'is_first'    => $page == 1,
                'is_last'     => $page >= $currentPage && !$perhapsMorePages,
                'offset'      => ($page-1) * $this->perPage
            ]));
        }

        return $pages;

    }

    /**
     * @param int $squeezeTo
     * @param int $numberOfPages
     *
     * @return Pages
     */
    protected function buildSqueezed($squeezeTo, $numberOfPages)
    {
        if ($this->currentPageNumber <= $squeezeTo) {
            return $this->buildStartEmphasized($squeezeTo, $numberOfPages);
        }

        if ($this->currentPageNumber > ($numberOfPages - $squeezeTo)) {
            return $this->buildEndEmphasized($squeezeTo, $numberOfPages);
        }

        // centered
        return $this->buildCentered($squeezeTo, $numberOfPages);
    }

    /**
     * Build pages for one of the first pages. Many pages at the start
     * will be visible, less at the end.
     *
     * @param int $squeezeTo
     * @param int $numberOfPages
     *
     * @return Pages
     */
    protected function buildStartEmphasized($squeezeTo, $numberOfPages)
    {
        $pages = $this->newPages($numberOfPages);

        $this->addPages($pages, 1, $squeezeTo - static::$defaultSqueezeSpace);

        $this->addPlaceHolder($pages);

        $this->addPages($pages, $numberOfPages - (static::$defaultSqueezeSpace - 1), $numberOfPages);

        return $pages;

    }

    /**
     * Build pages for the last pages. Many pages at the end will be visible,
     * less at the start.
     *
     * @param int $squeezeTo
     * @param int $numberOfPages
     *
     * @return Pages
     */
    protected function buildEndEmphasized($squeezeTo, $numberOfPages)
    {
        $pages = $this->newPages($numberOfPages);

        $this->addPages($pages, 1, static::$defaultSqueezeSpace);

        $this->addPlaceHolder($pages);

        $leftOffset = $squeezeTo - (static::$defaultSqueezeSpace + 1);
        $this->addPages($pages, $numberOfPages - $leftOffset, $numberOfPages);

        return $pages;
    }

    /**
     * Build a paginator squeezed to the center. Many pages in the center will
     * be visible and just 1 at the start and one at the end.
     *
     * @param int $squeezeTo
     * @param int $numberOfPages
     *
     * @return Pages
     */
    protected function buildCentered($squeezeTo, $numberOfPages)
    {
        $pages = $this->newPages($numberOfPages);

        $midLength = $squeezeTo - 2;
        $midOffset = $this->currentPageNumber - $midLength/2;

        $this->addPages($pages, 1, 1);
        $this->addPlaceHolder($pages);

        $this->addPages($pages, $midOffset, $midOffset+$midLength-1);

        $this->addPlaceHolder($pages);

        $this->addPages($pages, $numberOfPages, $numberOfPages);

        return $pages;
    }

    /**
     * @param Pages $pages
     * @param int   $from
     * @param int   $to
     *
     * @return Pages
     */
    protected function addPages(Pages $pages, $from, $to)
    {

        $items = $from == 1 ? 0 : ($from-1) * $this->perPage;

        for ($page=$from; $page <= $to; $page++) {

            $items += $this->perPage;

            $pageObject = $this->newPage([
                'number'      => $page,
                'url'         => $this->baseUrl ? $this->baseUrl->query($this->pageParameterName, $page) : '',
                'is_current'  => $page == $this->currentPageNumber,
                'is_previous' => $page == $this->currentPageNumber-1,
                'is_next'     => $page == $this->currentPageNumber+1,
                'is_first'    => $page == 1,
                'is_last'     => $items > $this->totalCount,
                'offset'      => ($page-1) * $this->perPage
            ]);

            $pages->add($pageObject);

        }

        return $pages;
    }

    /**
     * Adds a placeholde (...) page to make it squeezed.
     *
     * @param Pages $pages
     */
    protected function addPlaceHolder(Pages $pages)
    {
        $pages->add($this->newPage([
            'number' => 0,
            'url' => '',
            'is_current' => false,
            'is_previous' => false,
            'is_next' => false,
            'is_first' => false,
            'is_last' => false,
            'offset' => -1
        ]));
    }

    /**
     * @param array $values
     *
     * @return Page
     */
    protected function newPage(array $values)
    {
        return new Page($values);
    }

    /**
     * @param int $totalPageCount
     *
     * @return Pages
     */
    protected function newPages($totalPageCount=0)
    {
       return new Pages($this, $totalPageCount);
    }

    /**
     * @param mixed $page
     *
     * @return bool
     */
    protected function isValidPageNumber($page)
    {
        return is_numeric($page) && $page >= 1 && filter_var($page, FILTER_VALIDATE_INT) !== false;
    }

}
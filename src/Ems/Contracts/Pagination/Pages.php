<?php
/**
 *  * Created by mtils on 06.01.18 at 06:03.
 **/

namespace Ems\Contracts\Pagination;


use ArrayAccess;
use ArrayIterator;
use Countable;
use Ems\Contracts\Model\Result;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Traversable;

/**
 * Class Pages
 *
 * The pages are a one time used object. If something in the pagination changes
 * you have to create a new Pages object.
 *
 * @package Ems\Contracts\Pagination
 */
class Pages implements Result, ArrayAccess, Countable
{
    /**
     * @var array
     */
    protected $pages = [];

    /**
     * @var int
     */
    protected $totalPageCount = 0;

    /**
     * @var Paginator
     */
    protected $creator;

    /**
     * @var bool
     */
    protected $isSqueezed = false;

    /**
     * @var Page
     */
    protected $firstPage;

    /**
     * @var Page
     */
    protected $lastPage;

    /**
     * @var Page
     */
    protected $currentPage;

    /**
     * @var Page
     */
    protected $previousPage;

    /**
     * @var Page
     */
    protected $nextPage;

    /**
     * Pages constructor.
     *
     * @param Paginator $paginator
     * @param int       $totalPageCount (optional)
     */
    public function __construct(Paginator $paginator, $totalPageCount=0)
    {
        $this->creator = $paginator;
        $this->totalPageCount = $totalPageCount;
    }

    /**
     * Add a new page.
     *
     * @param Page $page
     *
     * @return $this
     */
    public function add(Page $page)
    {
        $next = count($this->pages) + 1;

        $this->pages[$next] = $page;

        if ($page->isPlaceholder()) {
            $this->isSqueezed = true;
        }

        if ($page->isFirst()) {
            $this->firstPage = $page;
        }

        if ($page->isLast()) {
            $this->lastPage = $page;
        }

        if ($page->isCurrent()) {
            $this->currentPage = $page;
        }

        if ($page->isPrevious()) {
            $this->previousPage = $page;
        }

        if ($page->isNext()) {
            $this->nextPage = $page;
        }

        return $this;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->pages);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->pages[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->pages[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        throw new UnsupportedUsageException('You cannot set Pages directly. Use self::add()');
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        throw new UnsupportedUsageException('You unset Pages. Use a new Pages object.');
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
        return count($this->pages);
    }

    /**
     * @return Page|null
     */
    public function first()
    {
        return $this->firstPage;
    }

    /**
     * @return Page|null
     */
    public function last()
    {
        return $this->lastPage;
    }

    /**
     * @return Page|null
     */
    public function current()
    {
        return $this->currentPage;
    }

    /**
     * @return Page|null
     */
    public function previous()
    {
        return $this->previousPage;
    }

    /**
     * @return Page|null
     */
    public function next()
    {
        return $this->nextPage;
    }

    /**
     * @return bool
     */
    public function hasOnlyOnePage()
    {
        return $this->count() == 1;
    }

    /**
     * @return bool
     */
    public function hasMoreThanOnePage()
    {
        return $this->count() > 1;
    }

    /**
     * Return the total amount of pages. This only differs when you have a
     * squeezed pagination.
     *
     * @return int
     */
    public function totalPageCount()
    {
        return $this->totalPageCount ? $this->totalPageCount : $this->count();
    }

    /**
     * Return true if this pages are squeezed (have placeholders in the middle)
     *
     * @return bool
     */
    public function isSqueezed()
    {
        return $this->isSqueezed;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() == 0;
    }

    /**
     * @return Paginator
     */
    public function creator()
    {
        return $this->creator;
    }
}
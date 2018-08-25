<?php
/**
 *  * Created by mtils on 24.08.18 at 16:51.
 **/

namespace Ems\Model;

use Ems\Contracts\Model\Paginatable;

/**
 * Trait PaginatableSearchTrait
 *
 * Make your Search Paginatable with this trait.
 *
 * @see Paginatable
 *
 * @package Ems\Model
 *
 */
trait PaginatableSearchTrait
{
    /**
     * @var array
     */
    protected $_paginationCache = [];

    /**
     *
     * @param array    $filters
     * @param array    $sorting
     * @param string[] $queryKeys
     * @param int      $page (default:1)
     * @param int      $perPage (default: 15)
     *
     * @return \Traversable
     */
    protected abstract function createPaginator(array $filters, array $sorting, $queryKeys, $page = 1, $perPage = 15);


    /**
     * Paginate the result. Return whatever paginator you use.
     * The paginator should be \Traversable.
     *
     * @param int $page (optional)
     * @param int $perPage (optional)
     *
     * @return \Traversable|array A paginator instance or just an array
     **/
    public function paginate($page = 1, $perPage = 15)
    {
        $cacheKey = "$page|$perPage";

        if (isset($this->_paginationCache[$cacheKey])) {
            return $this->_paginationCache[$cacheKey];
        }

        $this->parseInputOnce();

        $this->_paginationCache[$cacheKey] = $this->createPaginator(
            $this->filters,
            $this->sorting(),
            $this->queryKeys(),
            $page,
            $perPage
        );

        return $this->_paginationCache[$cacheKey];

    }

    /**
     * This method is called by AbstractSearch
     */
    protected function invalidateCachedResultsPaginatableSearchTrait()
    {
        $this->_paginationCache = [];
    }
}
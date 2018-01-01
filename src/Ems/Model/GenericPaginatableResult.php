<?php

namespace Ems\Model;

use function array_slice;
use Ems\Contracts\Model\PaginatableResult;
use function iterator_to_array;
use Traversable;

class GenericPaginatableResult extends GenericResult implements PaginatableResult
{
    /**
     * @var callable
     **/
    protected $paginatorCreator;

    /**
     * Pass a callable which will return the complete result.
     *
     * @param callable|Traversable|array $getter
     * @param callable $paginatorCreator (optional)
     * @param object   $creator          (optional)
     **/
    public function __construct($getter, callable $paginatorCreator=null, $creator = null)
    {
        parent::__construct($getter, $creator);
        $this->paginatorCreator = $paginatorCreator;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $page    (optional)
     * @param int $perPage (optional)
     *
     * @return \Traversable|array A paginator instance or just an array
     **/
    public function paginate($page = 1, $perPage = 15)
    {
        if ($this->paginatorCreator) {
            return call_user_func($this->paginatorCreator, $page, $perPage);
        }

        $all = iterator_to_array($this->getIterator());

        return array_slice($all, ($page-1)*$perPage, $perPage);
    }
}

<?php

namespace Ems\Model;

use Ems\Contracts\Model\PaginatableResult;
use UnexpectedValueException;


class GenericPaginatableResult extends GenericResult implements PaginatableResult
{

    /**
     * @var callable
     **/
    protected $paginatorCreator;


    /**
     * Pass a callable which will return the complete result
     *
     * @param callable $getter
     * @param callable $paginatorCreator
     * @param object $creator (optional)
     **/
    public function __construct(callable $getter, callable $paginatorCreator, $creator=null)
    {
        parent::__construct($getter, $creator);
        $this->paginatorCreator = $paginatorCreator;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $page (optional)
     * @param int $perPage (optional)
     * @return \Traversable|array A paginator instance or just an array
     **/
    public function paginate($page=1, $perPage=15)
    {
        return call_user_func($this->paginatorCreator, $page, $perPage);
    }

}

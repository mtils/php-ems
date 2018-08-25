<?php
/**
 *  * Created by mtils on 25.08.18 at 10:59.
 **/

namespace Ems\Model;

use ArrayIterator;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Model\OrmObject as OrmObjectContract;
use Ems\Contracts\Model\Paginatable;
use Ems\Pagination\Paginator;
use Traversable;
use function call_user_func;
use function is_callable;

/**
 * Class GenericSearch
 *
 * The GenericSearch is a small helper class to simply put any data into systems
 * that awaits Search objects.
 * It does Pagination on its own if you want to. You can either directly assign
 * a result or a callable that will produce the result.
 * If you want to make pagination on your own you have to assign a paginator.
 *
 * @package Ems\Model
 */
class GenericSearch extends AbstractSearch implements Paginatable
{
    use PaginatableSearchTrait;

    /**
     * @var \Traversable|array
     */
    protected $result;

    /**
     * @var callable
     */
    protected $resultProvider;

    /**
     * @var callable
     */
    protected $paginatorProvider;

    /**
     * GenericSearch constructor.
     *
     * @param OrmObjectContract $ormObject
     * @param array|Traversable $result (optional)
     */
    public function __construct(OrmObjectContract $ormObject, $result = null)
    {

        if ($ormObject) {
            $this->setOrmObject($ormObject);
        }

        if (is_callable($result)) {
            $this->provideResultBy($result);
            return $this;
        }

        if (Type::is($result, Traversable::class)) {
            $this->setResult($result);
        }

    }

    /**
     * @return array|Traversable
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param Traversable|array $result
     *
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * Assign a callable to create the result.
     * The following parameters will be passed to the provider:
     * $filters, $sorting, $queryKeys, $this
     *
     * @param callable $resultProvider
     *
     * @return $this
     */
    public function provideResultBy(callable $resultProvider)
    {
        $this->resultProvider = $resultProvider;
        $this->result = null;
        return $this;
    }

    /**
     * Assign a custom paginator provider to bypass the automatic creation.
     * The following parameters will be passed to the provider:
     * $page, $perPage, $filters, $sorting, $queryKeys, $this
     *
     * @param callable $paginatorProvider
     *
     * @return $this
     */
    public function providePaginatorBy(callable $paginatorProvider)
    {
        $this->paginatorProvider = $paginatorProvider;
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function createTraversable(
        array $filters,
        array $sorting,
        $queryKeys
    ) {

        if($result = $this->getResult()) {
            return is_array($result) ? new ArrayIterator($result) : $result;
        }

        if (!$this->resultProvider) {
            return new ArrayIterator([]);
        }

        $result = call_user_func($this->resultProvider, $filters, $sorting, $queryKeys, $this);

        return is_array($result) ? new ArrayIterator($result) : $result;
    }

    /**
     * @inheritDoc
     */
    protected function createPaginator(
        array $filters,
        array $sorting,
        $queryKeys,
        $page = 1,
        $perPage = 15
    ) {
        if ($this->paginatorProvider) {
            return call_user_func($this->paginatorProvider, $page, $perPage, $filters, $sorting, $queryKeys, $this);
        }


        $all = Type::toArray($this->getIterator());
        $paginator = new Paginator($page, $perPage, $this);

        return $paginator->setResult($paginator->slice($all), count($all));
    }


}
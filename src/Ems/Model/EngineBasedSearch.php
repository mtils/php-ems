<?php
/**
 *  * Created by mtils on 25.08.18 at 10:43.
 **/

namespace Ems\Model;

use Ems\Contracts\Core\Type;
use Ems\Contracts\Expression\ConditionGroup as ConditionGroupContract;
use Ems\Contracts\Expression\Queryable;
use Ems\Contracts\Model\OrmObject as OrmObjectContract;
use Ems\Contracts\Model\PaginatableResult;
use Ems\Contracts\Model\SearchEngine;
use Ems\Core\Exceptions\NotImplementedException;
use function get_class;
use function is_array;

/**
 * Class EngineBasedSearch
 *
 * The EngineBasedSearch is a simple class to realize a Search by a SearchEngine.
 *
 * @package Ems\Model
 */
class EngineBasedSearch extends AbstractSearch implements PaginatableResult
{
    use PaginatableSearchTrait;

    /**
     * @var SearchEngine
     */
    protected $engine;

    /**
     * AbstractSearch constructor.
     *
     * @param SearchEngine $engine
     * @param OrmObjectContract|null $ormObject
     * @param null $creator
     */
    public function __construct(SearchEngine $engine, OrmObjectContract $ormObject=null, $creator=null)
    {
        $this->engine = $engine;
        $this->ormObject = $ormObject;
        $this->_creator = $creator;
    }

    /**
     * Build the Condition out of the filters
     *
     * @param array $filters
     *
     * @return ConditionGroupContract
     */
    protected function buildConditions(array $filters)
    {
        $conditions = $this->engine->newQuery();

        foreach ($filters as $key=>$value) {
            $conditions = $this->buildCondition($conditions, $key, $value);
        }

        return $conditions;
    }

    /**
     * Add a single condition, add it to the group and return the new group.
     *
     * @param ConditionGroupContract $group
     * @param string                 $key
     * @param mixed                  $value
     *
     * @return ConditionGroupContract|Queryable
     */
    protected function buildCondition(ConditionGroupContract $group, $key, $value)
    {

        if (is_array($value)) {
            return $group->where($key, 'in', $value);
        }

        if (is_numeric($value)) {
            return $group->where($key, '=', $value);
        }

        if (Type::isStringLike($value)) {
            return $group->where($key, 'like', "$value");
        }

        return $group->where($key, $value);

    }

    /**
     * Get result from Search Engine
     *
     * @param array    $filters
     * @param array    $sorting
     * @param string[] $queryKeys
     *
     * @return \Ems\Contracts\Model\Result
     */
    protected function getResultFromEngine($filters, $sorting, $queryKeys)
    {
        return $this->engine->search($this->buildConditions($filters), $sorting, $queryKeys);
    }

    /**
     * @inheritDoc
     */
    protected function createTraversable(
        array $filters,
        array $sorting,
        $queryKeys
    ) {
        $result = $this->getResultFromEngine($filters, $sorting, $queryKeys);
        return $result->getIterator();
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

        $result = $this->getResultFromEngine($filters, $sorting, $queryKeys);

        if (!$result instanceof PaginatableResult) {
            $engine = get_class($this->engine);
            throw new NotImplementedException("SearchEngine $engine does not support pagination.");
        }

        return $result->paginate($page, $perPage);
    }
}
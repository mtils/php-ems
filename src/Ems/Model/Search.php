<?php
/**
 *  * Created by mtils on 28.12.17 at 06:52.
 **/

namespace Ems\Model;

use Ems\Contracts\Core\Type;
use Ems\Contracts\Expression\ConditionGroup as ConditionGroupContract;
use Ems\Contracts\Expression\Queryable;
use Ems\Contracts\Model\OrmObject as OrmObjectContract;
use Ems\Contracts\Model\PaginatableResult;
use Ems\Contracts\Model\Search as SearchContract;
use Ems\Contracts\Model\SearchEngine;
use Ems\Core\Exceptions\NotImplementedException;
use function get_class;
use function is_array;
use Traversable;

/**
 * Class Search
 *
 * The default Search implementation searches by a SearchEngine
 * and has a very simple expression builder.
 * Inherit from this class and overwrite buildExpression should
 * be enough to write your custom searches.
 *
 * @package Ems\Model
 */
class Search implements SearchContract, PaginatableResult
{
    use ResultTrait;
    use OrmCollectionMethods;
    use SearchMethods {
        SearchMethods::apply as parentApply;
        SearchMethods::filter as parentFilter;
    }

    /**
     * @var SearchEngine
     */
    protected $engine;

    /**
     * @var ConditionGroupContract
     */
    protected $conditions;

    /**
     * @var array
     */
    protected $paginationCache = [];

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
     * {@inheritdoc}
     * (Reimplemented to reset the cache)
     *
     * @param array $input
     *
     * @return $this
     */
    public function apply(array $input)
    {
        $this->conditions = null;
        $this->paginationCache = [];
        return $this->parentApply($input);
    }

    /**
     * {@inheritdoc}
     * (Reimplemented to reset the cache)
     *
     * @param array|string $key
     * @param mixed        $value (optional)
     *
     * @return $this
     */
    public function filter($key, $value = null)
    {
        $this->conditions = null;
        $this->paginationCache = [];
        return $this->parentFilter($key, $value);
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
        return $this->getFromEngine()->getIterator();
    }

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

        if (isset($this->paginationCache[$cacheKey])) {
            return $this->paginationCache[$cacheKey];
        }

        $result = $this->getFromEngine();

        if (!$result instanceof PaginatableResult) {
            $engine = get_class($this->engine);
            throw new NotImplementedException("SearchEngine $engine does not support pagination.");
        }

        $this->paginationCache[$cacheKey] = $result->paginate($page, $perPage);

        return $this->paginationCache[$cacheKey];

    }


    /**
     * Return an list keys (should be strings)
     *
     * @return \Ems\Core\Collections\OrderedList
     **/
    public function keys()
    {
        return $this->ormObject()->keys();
    }

    /**
     * These are the keys that will be passed to the search engine.
     *
     * @return array
     */
    protected function queryKeys()
    {
        return $this->ormObject()->keys()->getSource();
    }

    /**
     * @return ConditionGroupContract
     */
    protected function getConditions()
    {
        if ($this->conditions) {
            return $this->conditions;
        }

        $this->conditions = $this->engine->newQuery();

        foreach ($this->filters as $key=>$value) {
            $this->conditions = $this->buildCondition($this->conditions, $key, $value);
        }

        return $this->conditions;
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
     * Get result from SearchEngine.
     *
     * @return \Ems\Contracts\Model\Result
     */
    protected function getFromEngine()
    {
        $this->parseInputOnce();
        return $this->engine->search($this->getConditions(), $this->sorting(), $this->queryKeys());
    }
}
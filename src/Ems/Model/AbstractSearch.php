<?php
/**
 *  * Created by mtils on 24.08.18 at 15:57.
 **/

namespace Ems\Model;

use Ems\Core\Support\TraitMethods;
use Ems\Contracts\Model\Search as SearchContract;
use Traversable;

abstract class AbstractSearch implements SearchContract
{
    use TraitMethods;
    use ResultTrait;
    use OrmCollectionMethods;
    use SearchMethods {
        SearchMethods::apply as parentApply;
        SearchMethods::filter as parentFilter;
    }

    /**
     * @var Traversable
     */
    protected $iteratorCache;

    /**
     *
     * @param array    $filters
     * @param array    $sorting
     * @param string[] $queryKeys
     *
     * @return \Traversable
     */
    protected abstract function createTraversable(array $filters, array $sorting, $queryKeys);

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {

        if ($this->iteratorCache) {
            return $this->iteratorCache;
        }

        $this->parseInputOnce();

        $this->iteratorCache = $this->createTraversable($this->filters, $this->sorting(), $this->queryKeys());

        return $this->iteratorCache;
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
        $this->invalidateCachedResults();
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
        $this->invalidateCachedResults();
        return $this->parentFilter($key, $value);
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
     * These are the keys that will be passed to createTraversable().
     *
     * @return array
     */
    protected function queryKeys()
    {
        return $this->keys()->getSource();
    }

    /**
     * Invalidate all locally cached results
     */
    protected function invalidateCachedResults()
    {
        $this->iteratorCache = null;
        $this->callOnAllTraits('invalidateCachedResults');
    }
}
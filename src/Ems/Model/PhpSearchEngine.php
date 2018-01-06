<?php
/**
 *  * Created by mtils on 31.12.17 at 15:26.
 **/

namespace Ems\Model;


use function call_user_func;
use Ems\Contracts\Core\Extractor as ExtractorContract;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Expression\ConditionGroup;
use Ems\Contracts\Model\Result;
use Ems\Contracts\Model\SearchEngine;
use Ems\Core\Extractor;
use Ems\Expression\Matcher;
use Ems\Pagination\Paginator;
use function is_numeric;
use function is_string;
use function strnatcasecmp;
use Traversable;

/**
 * Class PhpSearchEngine
 *
 * The PhpSearchEngine is a search engine that completely works with php. It
 * supports filtering and sorting. You can throw big arrays in it and it will
 * behave like a database.
 *
 * @package Ems\Model
 */
class PhpSearchEngine implements SearchEngine
{
    /**
     * @var Matcher
     */
    protected $matcher;

    /**
     * @var ExtractorContract
     */
    protected $extractor;

    /**
     * @var array|Traversable
     */
    protected $data = [];

    /**
     * @var callable
     */
    protected $dataProvider;

    /**
     * PhpSearchEngine constructor.
     *
     * @param Matcher           $matcher
     * @param ExtractorContract $extractor (optional)
     */
    public function __construct(Matcher $matcher, ExtractorContract $extractor=null)
    {
        $this->matcher = $matcher;
        $this->extractor = $extractor ?: new Extractor();

    }

    /**
     * {@inheritdoc}
     *
     * @return ConditionGroup
     */
    public function newQuery()
    {
        return $this->matcher->where('foo', 'bar')->clear();
    }

    /**
     * {@inheritdoc}
     *
     * @param ConditionGroup $conditions
     * @param array $sorting (optional)
     * @param string[] $keys (optional)
     *
     * @return Result
     */
    public function search(ConditionGroup $conditions, array $sorting = [], $keys = [])
    {
        return new GenericPaginatableResult(
            function () use ($conditions, $sorting, $keys) {
                return $this->buildResult($conditions, $sorting, $keys);
            },
            function ($page, $perPage) use ($conditions, $sorting, $keys) {
                return $this->buildResult($conditions, $sorting, $keys, $page, $perPage);
            },
            $this
        );
    }

    /**
     * Return the assigned data.
     *
     * @return array|Traversable
     */
    public function getData()
    {
        if (!$this->data && $this->dataProvider) {
            return call_user_func($this->dataProvider, $this);
        }
        return $this->data;
    }

    /**
     * Assign all data.
     *
     * @param array|Traversable $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = Type::forceAndReturn($data, Traversable::class);
        return $this;
    }

    /**
     * Provide the data by a callable instead of assigning it directly.
     *
     * @param callable $provider
     *
     * @return $this
     */
    public function provideDataBy(callable $provider)
    {
        $this->dataProvider = $provider;
        return $this;
    }

    /**
     * Build the result array by filtering it and then sorting it.
     *
     * @param ConditionGroup $conditions
     * @param array          $sorting
     * @param array          $keys (optional)
     * @param int            $page (optional)
     * @param int            $perPage (optional)
     *
     * @return array|Paginator
     */
    protected function buildResult(ConditionGroup $conditions, array $sorting, $keys=[], $page=null, $perPage=null)
    {

        $matcher = $this->createMatcher($conditions);
        $shouldBreak = $page && $perPage && !$sorting;

        $offset = $shouldBreak ? ($page-1)*$perPage : 0;
        $limit = $shouldBreak ? $perPage : null;

        $filtered = $this->filterData($matcher, $offset, $limit);

        if ($shouldBreak) {
            // Create a "not length aware" paginator here
            $paginator = new Paginator($page, $perPage, $this);
            $paginator->setResult($filtered);
            return $paginator;
        }

        if ($sorting) {
            $this->sortResult($filtered, $sorting);
        }

        if (!$page || !$perPage) {
            return $filtered;
        }

        $paginator = new Paginator($page, $perPage, $this);

        return $paginator->setResult($paginator->slice($filtered), count($filtered));

    }

    /**
     * Filter the data and optionally slice it.
     *
     * @param callable $matcher
     * @param int      $offset (optional)
     * @param int      $limit (optional)
     *
     * @return array
     */
    protected function filterData(callable $matcher, $offset=0, $limit=null)
    {
        $filtered = [];

        $i=-1;
        $added = 0;

        foreach ($this->getData() as $item) {

            if (!$matcher($item)) {
                continue;
            }

            $i++;

            if ($i < $offset) {
                continue;
            }

            $filtered[] = $item;
            $added++;

            if ($limit !== null && $added >= $limit) {
                return $filtered;
            }

        }

        return $filtered;

    }

    /**
     * @param array $result
     * @param array $sorting
     */
    protected function sortResult(array &$result, array $sorting)
    {
        usort($result, function ($a, $b) use ($sorting) {
            return $this->sortCompare($a, $b, $sorting);
        });
    }

    /**
     * Compare two results by $sorting and return -1, 0 or 1.
     *
     * @param mixed $a
     * @param mixed $b
     * @param array $sorting
     *
     * @return int
     */
    protected function sortCompare($a, $b, $sorting)
    {
        $position = 0;

        foreach ($sorting as $key=>$direction) {

            $left = $this->extractor->value($a, $key);
            $right = $this->extractor->value($b, $key);

            $position = $this->applyDirection($this->sortCompareValues($left, $right), $direction);

            if ($position !== 0) {
                return $position;
            }
        }

        return $position;

    }

    /**
     * Compare arbitrary values for sorting.
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @return int
     */
    protected function sortCompareValues($a, $b)
    {
        if (is_numeric($a) && is_numeric($b)) {
            return $this->sortCompareNumeric((float)$a, (float)$b);
        }

        if (is_string($a) && is_string($b)) {
            return $this->sortCompareStrings($a, $b);
        }

        return ($a < $b) ? -1 : (int)($a > $b);
    }

    /**
     * @param float $a
     * @param float $b
     *
     * @return int
     */
    protected function sortCompareNumeric($a, $b)
    {
        return ($a < $b) ? -1 : (int)($a > $b);
    }

    /**
     * @param $a
     * @param $b
     *
     * @return int
     */
    protected function sortCompareStrings($a, $b)
    {
        return strnatcasecmp($a, $b);
    }

    /**
     * @param int    $sort
     * @param string $direction
     *
     * @return int
     */
    protected function applyDirection($sort, $direction)
    {
        return ($direction == 'asc') ? $sort : 0 - $sort;
    }

    /**
     * @param ConditionGroup $conditions
     *
     * @return callable
     */
    protected function createMatcher(ConditionGroup $conditions)
    {
        if (!$conditions->hasConditions()) {
            return function () { return true; };
        }
        return $this->matcher->compile($conditions);
    }
}
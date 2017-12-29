<?php
/**
 *  * Created by mtils on 27.12.17 at 05:51.
 **/

namespace Ems\Model;
use function array_search;

/**
 * Trait CanSortMethods
 *
 * @see \Ems\Contracts\Model\CanSort
 *
 * @package Ems\Model
 */
trait CanSortMethods
{
    /**
     * @var array
     */
    protected $sorting = [];

    /**
     * @var bool
     */
    protected $sortingBooted = false;

    /**
     * {@inheritdoc}
     *
     * @param string|array $key
     * @param string $direction (default: 'asc')
     *
     * @return $this
     */
    public function sort($key, $direction=self::ASC)
    {
        $this->bootCanSortMethodsOnce();
        $this->sorting[$key] = $direction;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key (optional)
     *
     * @return $this
     */
    public function clearSort($key=null)
    {
        if (!$key) {
            $this->sorting = [];
            return $this;
        }

        $this->bootCanSortMethodsOnce();

        if (isset($this->sorting[$key])) {
            unset($this->sorting[$key]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key (optional)
     *
     * @return array|string|null
     */
    public function sorting($key=null)
    {
        $this->bootCanSortMethodsOnce();

        if (!$key) {
            return $this->sorting;
        }

        if (isset($this->sorting[$key])) {
            return $this->sorting[$key];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return bool
     */
    public function isSortedBy($key)
    {
        return $this->sorting($key) !== null;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return int
     */
    public function sortingPriority($key)
    {
        $this->bootCanSortMethodsOnce();

        if (!isset($this->sorting[$key])) {
            return 0;
        }

        return array_search($key, array_keys($this->sorting)) + 1;
    }

    /**
     * Boot the sorting before accessing it.
     */
    protected function bootCanSortMethods()
    {
        //
    }

    /**
     * Boot the sorting if not done.
     */
    protected function bootCanSortMethodsOnce()
    {
        if (!$this->sortingBooted) {
            $this->bootCanSortMethods();
            $this->sortingBooted = true;
        }
    }
}
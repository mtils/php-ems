<?php
/**
 *  * Created by mtils on 27.12.17 at 06:04.
 **/

namespace Ems\Model;

use Ems\Contracts\Model\CanSort;
use Ems\Core\Collections\StringList;
use function array_key_exists;
use function array_map;
use function func_num_args;
use function is_numeric;

/**
 * Trait SearchMethods
 *
 * @see \Ems\Contracts\Model\Search
 *
 * @package Ems\Model
 */
trait SearchMethods
{
    use CanSortMethods;

    /**
     * @var array
     */
    protected $input = [];

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var bool
     */
    protected $inputParsed = false;

    /**
     * @var string
     */
    protected $sortParameter = '_sort';

    /**
     * {@inheritdoc}
     *
     * @param array $input
     *
     * @return $this
     */
    public function apply(array $input)
    {
        $this->input = $input;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key (optional)
     * @param mixed $default (optional)
     *
     * @return mixed
     */
    public function input($key=null, $default=null)
    {
        if (!$key) {
            return $this->input;
        }

        return array_key_exists($key, $this->input) ? $this->input[$key] : $default;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $key
     * @param mixed        $value (optional)
     *
     * @return $this
     */
    public function filter($key, $value=null)
    {

        $this->parseInputOnce();

        if (!is_array($key)) {
            $this->filters[$key] = $value;
            return $this;
        }

        foreach ($key as $filter=>$needle) {
            $this->filter($filter, $needle);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return mixed
     */
    public function filterValue($key)
    {
        $this->parseInputOnce();
        return isset($this->filters[$key]) ? $this->filters[$key] : null;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param mixed  $value (optional)
     *
     * @return bool
     */
    public function hasFilter($key, $value=null)
    {
        $this->parseInputOnce();

        if (!array_key_exists($key, $this->filters)) {
            return false;
        }

        if (func_num_args() < 2) {
            return true;
        }

        return $this->filterWouldMatch($this->filters[$key], $value, $key);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key (optional)
     *
     * @return $this
     */
    public function clearFilter($key=null)
    {
        if (!$key) {
            $this->filters = [];
            return $this;
        }

        if (isset($this->filters[$key])) {
            unset($this->filters[$key]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return StringList
     */
    public function filterKeys()
    {
        $this->parseInputOnce();
        return new StringList(array_keys($this->filters));
    }

    /**
     * Add the input parameter to the filter. In opposite to filter() the input
     * can not be trusted.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function applyFilter($key, $value)
    {
        $this->filter($key, $value);
    }

    /**
     * Add the input parameter to the sorting.
     *
     * @param string|array $sort
     */
    protected function applySorting($sort)
    {
        if (is_array($sort)) {
            array_map([$this, 'applySorting'], $sort);
            return;
        }

        if (!strpos($sort, ':')) {
            $this->sort($sort, CanSort::ASC);
            return;
        }

        list($key, $direction) = explode(':', $sort, 2);

        $this->sort($key, $direction);

    }

    /**
     * Dispatch the parameter to sort, filter, ...
     *
     * @param $key
     * @param $value
     */
    protected function applyParameter($key, $value)
    {
        if ($key == $this->sortParameter) {
            $this->applySorting($value);
            return;
        }

        $this->applyFilter($key, $value);
    }

    /**
     * Dispatch the parameters to sort, filter, ...
     */
    protected function parseInput()
    {
        foreach ($this->input as $key=>$value) {
            $this->applyParameter($key, $value);
        }
    }

    /**
     * Parse the input before accessing filters.
     */
    protected function parseInputOnce()
    {
        if (!$this->inputParsed) {
            $this->inputParsed = true;
            $this->parseInput();
        }
    }

    /**
     * Boot the sort trait too.
     */
    protected function bootCanSortMethods()
    {
        $this->parseInputOnce();
    }

    /**
     * Overwrite this method to adjust the filter to value comparison.
     *
     * @param mixed $filterValue
     * @param mixed $value
     * @param string $key
     *
     * @return bool
     */
    protected function filterWouldMatch($filterValue, $value, $key)
    {
        if ($value === null) {
            return $filterValue === null;
        }

        if (is_array($filterValue)) {
            return is_array($value) ? $filterValue == $value : in_array($value, $filterValue);
        }

        if (is_numeric($value)) {
            return $filterValue == $value;
        }

        return mb_strpos($value, $filterValue) !== false;

    }
}
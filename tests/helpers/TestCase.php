<?php

namespace Ems;

use function count;
use Ems\Contracts\Core\Type;
use function is_callable;
use function is_scalar;
use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;
use function property_exists;
use Traversable;

class TestCase extends BaseTestCase
{
    public function mock($class)
    {
        $mock = Mockery::mock($class);
        return $mock;
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * Assert that $collection has an object with
     *
     * @param array|Traversable $collection
     * @param array|callable    $criterion
     * @param string            $message (optional)
     */
    protected function assertHasObjectWith($collection, $criterion, $message='')
    {

        $filtered = $this->getObjectsWith($collection, $criterion);
        $criteria = $this->formatCriteria($criterion);
        $message = $message ?: "Failed asserting that the passed collection contained the passed $criteria";
        $this->assertTrue(count($filtered) > 0, $message);
    }

    /**
     * Assert that $collection has $count (N) objects with the passed criterion,
     *
     * @param array|Traversable $collection
     * @param array|callable    $criterion
     * @param int               $count
     * @param string            $message (optional)
     */
    protected function assertHasNObjectWith($collection, $criterion, $count, $message='')
    {

        $filtered = $this->getObjectsWith($collection, $criterion);
        $realCount = count($filtered);
        $criteria = $this->formatCriteria($criterion);
        $message = $message ?: "Failed asserting that the passed collection contained $count items of the passed $criteria. It contained $realCount.";
        $this->assertTrue($realCount == $count, $message);
    }

    /**
     * Get the objects with $criterion out of $collection. Criterion can be an
     * array and every $key=>$value has to match a property with value. Or
     * you pass a callable to do the check by your own. Return true in your
     * callable to include the object in the result, false to exclude it.
     *
     * @param array|Traversable $collection
     * @param array|callable     $criterion
     *
     * @return array
     */
    protected function getObjectsWith($collection, $criterion)
    {

        $f = is_callable($criterion) ? $criterion : function ($object) use ($criterion) {
            foreach ($criterion as $key=>$value) {
                if (!isset($object->$key)) {
                    return false;
                }
                if ($object->$key != $value) {
                    return false;
                }
            }
            return true;
        };

        $filtered = [];

        foreach ($collection as $item) {
            if ($f($item)) {
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

    /**
     * @param callable|array $criteria
     *
     * @return string
     */
    protected function formatCriteria($criteria)
    {
        if (is_callable($criteria)) {
            return 'criteria (callable)';
        }
        $items = [];
        foreach ($criteria as $key=>$value){
            $formattedValue = Type::isStringable($value) ? $value : Type::of($value);
            $items[] = "$key=$formattedValue";
        }
        return 'criteria (' . implode(', ', $items) . ')';
    }
}
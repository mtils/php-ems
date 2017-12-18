<?php
/**
 *  * Created by mtils on 17.12.17 at 07:50.
 **/

namespace Ems\Contracts\Core\Containers;


use Ems\TestCase;

class PairTest extends TestCase
{

    public function test_new_instance()
    {
        $this->assertInstanceOf(Pair::class, $this->pair(1,13));
    }

    public function test_first_and_second()
    {
        $pair = $this->pair(15,16);
        $this->assertEquals(15, $pair->first());
        $this->assertEquals(16, $pair->second());

        $this->assertSame($pair, $pair->setFirst(22));
        $this->assertSame($pair, $pair->setSecond(23));

        $this->assertEquals(22, $pair->first());
        $this->assertEquals(23, $pair->second());
    }

    public function test_construct_with_other()
    {
        $other = $this->pair(15,16, 'int');

        $pair = $this->pair($other);

        $this->assertEquals(15, $pair->first());
        $this->assertEquals(16, $pair->second());

        $this->assertSame($pair, $pair->setFirst(22));
        $this->assertSame($pair, $pair->setSecond(23));

        $this->assertEquals(22, $pair->first());
        $this->assertEquals(23, $pair->second());
    }

    public function test_last_is_equals_to_second()
    {
        $pair = $this->pair(15,16);
        $this->assertEquals(16, $pair->second());
        $this->assertEquals($pair->second(), $pair->last());
    }

    public function test_isEmpty_returns_right_state()
    {
        $this->assertTrue($this->pair()->isEmpty());
        $this->assertFalse($this->pair(15,16)->isEmpty());
        $this->assertFalse($this->pair(23)->isEmpty());
        $this->assertFalse($this->pair(null, 23)->isEmpty());
    }

    public function test_isNull_returns_right_state()
    {
        $this->assertTrue($this->pair()->isNull());
        $this->assertFalse($this->pair(false, false)->isNull());
        $this->assertFalse($this->pair(15, -25)->isNull());
    }

    public function test_isValid_returns_right_state()
    {
        $this->assertTrue($this->pair()->isValid());
        $this->assertFalse($this->pair(null, null, 'int')->isValid());
        $this->assertTrue($this->pair(16, 25, 'int')->isValid());
    }

    public function test_copy_returns_new_instance()
    {
        $pair = $this->pair(15, 23);
        $this->assertEquals(15, $pair->first());
        $this->assertEquals(23, $pair->second());

        $pair2 = $pair->copy();
        $this->assertNotSame($pair, $pair2);
        $this->assertEquals(15, $pair2->first());
        $this->assertEquals(23, $pair2->second());
    }

    public function test_swap_swaps_values()
    {
        $pair = $this->pair(15, 23);
        $this->assertEquals(15, $pair->first());
        $this->assertEquals(23, $pair->second());

        $pair->swap();
        $this->assertEquals(23, $pair->first());
        $this->assertEquals(15, $pair->second());
    }

    public function test_swapped_swaps_values_and_returns_new_instance()
    {
        $pair = $this->pair(15, 23);
        $this->assertEquals(15, $pair->first());
        $this->assertEquals(23, $pair->second());

        $pair2 = $pair->swapped();
        $this->assertNotSame($pair, $pair2);
        $this->assertEquals(23, $pair2->first());
        $this->assertEquals(15, $pair2->second());
    }

    public function test_total_returns_sum_of_values()
    {
        $this->assertEquals(15, $this->pair(10,5)->total());
    }

    public function test_equals_returns_if_matches_other()
    {
        $pair1 = $this->pair(10,5);
        $pair2 = $this->pair(10,5);
        $pair3 = $this->pair(7,5);

        $this->assertTrue($pair1->equals($pair2));
        $this->assertTrue($pair2->equals($pair1));
        $this->assertFalse($pair1->equals($pair3));

    }

    public function test_isGreaterThan_does_work()
    {
        $pair1 = $this->pair(10,5);
        $pair2 = $this->pair(10,5);
        $pair3 = $this->pair(7,5);

        $this->assertFalse($pair1->isGreaterThan($pair2));
        $this->assertFalse($pair2->isGreaterThan($pair1));
        $this->assertTrue($pair1->isGreaterThan($pair3));
        $this->assertFalse($pair3->isGreaterThan($pair1));

    }

    public function test_isGreaterOrEqual_does_work()
    {
        $pair1 = $this->pair(10,5);
        $pair2 = $this->pair(10,5);
        $pair3 = $this->pair(7,5);

        $this->assertTrue($pair1->isGreaterOrEqual($pair2));
        $this->assertTrue($pair2->isGreaterOrEqual($pair1));
        $this->assertTrue($pair1->isGreaterOrEqual($pair3));

    }

    public function test_isLessThan_does_work()
    {
        $pair1 = $this->pair(10,5);
        $pair2 = $this->pair(10,5);
        $pair3 = $this->pair(7,5);

        $this->assertFalse($pair1->isLessThan($pair2));
        $this->assertFalse($pair2->isLessThan($pair1));
        $this->assertFalse($pair1->isLessThan($pair3));
        $this->assertTrue($pair3->isLessThan($pair1));

    }

    public function test_isLessOrEqual_does_work()
    {
        $pair1 = $this->pair(10,5);
        $pair2 = $this->pair(10,5);
        $pair3 = $this->pair(7,5);

        $this->assertTrue($pair1->isLessOrEqual($pair2));
        $this->assertTrue($pair2->isLessOrEqual($pair1));
        $this->assertFalse($pair1->isLessOrEqual($pair3));
        $this->assertTrue($pair3->isLessOrEqual($pair1));

    }

    public function test_offsetExists()
    {
        $this->assertTrue($this->pair()->offsetExists(1));
        $this->assertTrue($this->pair()->offsetExists(2));
        $this->assertFalse($this->pair()->offsetExists(3));
        $this->assertFalse($this->pair()->offsetExists(0));

    }

    public function test_offsetGet()
    {
        $this->assertEquals(15, $this->pair(15,44)[1]);
        $this->assertEquals(44, $this->pair(15,44)[2]);

    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function test_offsetGet_throws_exception_on_wrong_index()
    {
        $this->pair(15,44)[0];
    }

    public function test_offsetSet()
    {
        $pair = $this->pair(44,43);
        $pair[1] = 45;
        $pair[2] = 44;
        $this->assertEquals(45, $pair[1]);
        $this->assertEquals(44, $pair[2]);
    }

    public function test_offsetUnset()
    {
        $pair = $this->pair(44,43);
        unset($pair[1]);
        $this->assertNull($pair[1]);
    }

    protected function pair($first=null, $second=null, $type=null)
    {
        return new Pair($first, $second, $type);
    }
}
<?php

namespace Ems\Core\Collections;

use Ems\Testing\LoggingCallable;
use InvalidArgumentException;
use OutOfBoundsException;
use OutOfRangeException;

class OrderedListTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceof('ArrayAccess', $this->newList());
        $this->assertInstanceof('IteratorAggregate', $this->newList());
        $this->assertInstanceof('Countable', $this->newList());
    }

    public function test_construct_with_params_fills_list()
    {
        $this->assertEquals(['a', 'b'], $this->newList(['a', 'b'])->getSource());
    }

    public function test_append_appends_a_value()
    {
        $list = $this->newList()->append('a');
        $this->assertEquals(['a'], $list->getSource());
        $list->append('b')->append('c');
        $this->assertEquals(['a', 'b', 'c'], $list->getSource());
    }

    public function test_push_appends_a_value()
    {
        $list = $this->newList()->push('a');
        $this->assertEquals(['a'], $list->getSource());
    }

    public function test_prepend_prepends_a_value()
    {
        $list = $this->newList(['c', 'd', 'e']);
        $this->assertEquals(['b', 'c', 'd', 'e'], $list->prepend('b')->getSource());
        $this->assertEquals(['a', 'b', 'c', 'd', 'e'], $list->prepend('a')->getSource());
    }

    public function test_insert_appends_a_value()
    {
        $list = $this->newList(['c', 'd', 'e']);
        $this->assertEquals(['c', 'd', 'e', 'f'], $list->insert(count($list), 'f')->getSource());
    }

    public function test_insert_before_zero_throws_exception()
    {
        $this->expectException(OutOfRangeException::class);
        $list = $this->newList(['c', 'd', 'e']);
        $list->insert(-1, 'f');
    }

    public function test_insert_after_count_throws_exception()
    {
        $this->expectException(OutOfRangeException::class);
        $list = $this->newList(['c', 'd', 'e']);
        $list->insert(5, 'h');
    }

    public function test_indexOf_finds_index_on_string()
    {
        $list = $this->newList(['c', 'd', 'e']);
        $this->assertEquals(0, $list->indexOf('c'));
        $this->assertEquals(1, $list->indexOf('d'));
        $this->assertEquals(2, $list->indexOf('e'));
    }

    public function test_contains_returns_bool_and_doesnt_throw_exceptions()
    {
        $list = $this->newList(['c', 'd', 'e']);
        $this->assertTrue($list->contains('c'));
        $this->assertFalse($list->contains('i'));
        $this->assertTrue($list->contains('e'));
    }

    public function test_pop_removes_last_value()
    {
        $list = $this->newList(['c', 'd', 'e']);
        $this->assertEquals(['c', 'd', 'e'], $list->getSource());
        $this->assertEquals('e', $list->pop());
        $this->assertEquals(['c', 'd'], $list->getSource());
    }

    public function test_pop_removes_value_in_middle()
    {
        $list = $this->newList(['c', 'd', 'e']);
        $this->assertEquals(['c', 'd', 'e'], $list->getSource());
        $this->assertEquals('d', $list->pop(1));
        $this->assertEquals(['c', 'e'], $list->getSource());
    }

    public function test_indexOf_throws_exception_if_value_not_found()
    {
        $this->expectException(OutOfBoundsException::class);
        $list = $this->newList(['c', 'd', 'e']);
        $list->indexOf('h');
    }

    public function test_remove_removes_string()
    {
        $list = $this->newList(['c', 'd', 'e']);
        $this->assertEquals(['c', 'd', 'e'], $list->getSource());
        $this->assertEquals('d', $list->remove('d'));
        $this->assertEquals(['c', 'e'], $list->getSource());
    }

    public function test_count_with_params_counts_values()
    {
        $list = $this->newList(str_split('abbcccdeeeee'));

        $this->assertEquals(1, $list->count('a'));
        $this->assertEquals(2, $list->count('b'));
        $this->assertEquals(3, $list->count('c'));
        $this->assertEquals(1, $list->count('d'));
        $this->assertEquals(5, $list->count('e'));
    }

    public function test_sort_sorts_array_alphabetical()
    {
        $list = $this->newList(str_split('feddcba'));
        $this->assertEquals(str_split('abcddef'), $list->sort()->getSource());
    }

    public function test_reverse_sorts_reverse()
    {
        $list = $this->newList(str_split('fedcba'));
        $this->assertEquals(str_split('abcdef'), $list->reverse()->getSource());
    }

    public function test_unique_removes_dupliate_strings()
    {
        $list = $this->newList(str_split('abcdddefffg'));
        $this->assertEquals(str_split('abcdefg'), $list->unique()->getSource());
    }

    public function test_apply_calls_on_every_item()
    {
        $list = $this->newList(str_split('abcdef'));
        $callable = new LoggingCallable();

        $list->apply($callable);
        $this->assertCount(count($list), $callable);

        foreach ($list as $i=>$value) {
            $this->assertEquals($value, $callable->arg(0, $i));
        }
    }

    public function test_filter_filters_items()
    {
        $list = $this->newList(str_split('abcdef'));

        $filter = function ($item) {
            return $item != 'c';
        };

        $this->assertEquals(str_split('abdef'), $list->filter($filter)->getSource());
    }

    public function test_find_finds_item()
    {
        $list = $this->newList(str_split('abcdef'));

        $filter = function ($item) {
            return $item == 'c';
        };

        $this->assertEquals('c', $list->find($filter));
    }

    public function test_find_throws_exception_on_miss()
    {
        $this->expectException(
            ItemNotFoundException::class
        );
        $list = $this->newList(str_split('abcdef'));

        $filter = function ($item) {
            return $item == 'r';
        };

        $list->find($filter);
    }

    public function test_offsetGet_throws_exception_on_miss()
    {
        $this->expectException(OutOfRangeException::class);
        $list = $this->newList(str_split('abcdef'));
        $list[8];
    }

    public function test_offsetSet_sets_value()
    {
        $list = $this->newList(str_split('abcdef'));
        $list[6] = 'g';
        $this->assertEquals(str_split('abcdefg'), $list->getSource());
    }

    public function test_offsetExists()
    {
        $list = $this->newList(str_split('abcdef'));
        $this->assertTrue(isset($list[0]));
        $this->assertTrue(isset($list[3]));
        $this->assertFalse(isset($list[53]));
    }

    public function test_offsetUnset()
    {
        $list = $this->newList(str_split('abcdef'));
        unset($list[5]);
        $this->assertEquals(str_split('abcde'), $list->getSource());
    }

    public function test_construct_with_string_splits()
    {
        $this->assertEquals(str_split('abcdef'), $this->newList('abcdef')->getSource());
    }

    public function test_construct_with_int_creates_range()
    {
        $this->assertEquals(range(0, 10), $this->newList(10)->getSource());
    }

    public function test_construct_with_char_creates_range()
    {
        $this->assertEquals(range('A', 'Z'), $this->newList('Z')->getSource());
        $this->assertEquals(range('a', 'z'), $this->newList('z')->getSource());
    }

    public function test_construct_with_object_takes_items()
    {
        $list1 = $this->newList('abcdefg');
        $list2 = $this->newList($list1);
        $this->assertEquals($list1->getSource(), $list2->getSource());
    }

    public function test_construct_with_unteraversable_object_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $list1 = $this->newList(new \stdClass());
    }

    public function test_first_returns_first_item()
    {
        $this->assertEquals('a',  $this->newList(str_split('abcdef'))->first());
        $this->assertNull($this->newList()->first());
    }

    public function test_last_returns_last_item()
    {
        $this->assertEquals('f',  $this->newList(str_split('abcdef'))->last());
        $this->assertNull($this->newList()->last());
    }

    public function test_copy_returns_new_instance()
    {
        $list = $this->newList(str_split('bcdef'));
        $copy = $list->copy();
        $this->assertNotSame($list, $copy);
        $this->assertEquals($list->getSource(), $copy->getSource());
        $list->append('g');
        $copy->prepend('a');

        $this->assertEquals(str_split('bcdefg'), $list->getSource());
        $this->assertEquals(str_split('abcdef'), $copy->getSource());
    }

    public function test_clone_returns_new_instance()
    {
        $list = $this->newList(str_split('bcdef'));
        $copy = clone $list;
        $this->assertNotSame($list, $copy);
        $this->assertEquals($list->getSource(), $copy->getSource());
        $list->append('g');
        $copy->prepend('a');

        $this->assertEquals(str_split('bcdefg'), $list->getSource());
        $this->assertEquals(str_split('abcdef'), $copy->getSource());
    }

    protected function newList($params=null)
    {
        return new OrderedList($params);
    }
}

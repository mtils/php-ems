<?php
/**
 *  * Created by mtils on 30.07.2022 at 21:58.
 **/

namespace unit\Core;

use ArrayAccess;
use ArrayObject;
use Ems\Contracts\Core\ArrayData;
use Ems\Contracts\Model\Filterable;
use Ems\Core\FilterableArray;
use Ems\TestCase;
use IteratorAggregate;
use TypeError;

use function print_r;

class FilterableArrayTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interfaces()
    {
        $this->assertInstanceOf(Filterable::class, $this->make());
        $this->assertInstanceOf(ArrayData::class, $this->make());
        $this->assertInstanceOf(IteratorAggregate::class, $this->make());
    }

    /**
     * @test
     */
    public function get_and_set_source()
    {
        $source = ['foo' => 'bar'];
        $array = $this->make();
        $this->assertSame([], $array->getSource());
        $this->assertSame($array, $array->setSource($source));
        $this->assertSame($source, $array->getSource());
    }

    /**
     * @test
     */
    public function set_non_array_Like_source_throws_error()
    {
        $this->expectException(TypeError::class);
        $this->make('hello');
    }

    /**
     * @test
     */
    public function set_non_iterable_source_throws_error()
    {
        $this->expectException(TypeError::class);
        $this->make(new class () implements ArrayAccess {
            function offsetExists($offset){}
            public function offsetGet($offset){}
            public function offsetSet($offset, $value){}
            public function offsetUnset($offset){}
        });
    }

    /**
     * @test
     */
    public function ArrayAccess_forwards_to_source()
    {
        $source = ['foo' => 'bar', 'a' => 'b', 'all' => [1,2,3]];
        $array = $this->make($source);
        $this->assertTrue(isset($array['foo']));
        $this->assertFalse(isset($array['bar']));
        $this->assertEquals($source['foo'], $array['foo']);
        $this->assertEquals($source['a'], $array['a']);
        $array['a'] = 'c';
        $this->assertEquals('c', $array['a']);
        $this->assertSame($source['all'], $array['all']);
        unset($array['all']);
        $this->assertFalse(isset($array['all']));
    }

    /**
     * @test
     */
    public function toArray_returns_array()
    {
        $source = ['foo' => 'bar', 'a' => 'b', 'all' => [1,2,3]];
        $array = $this->make($source);
        $this->assertSame($source, $array->toArray());
    }

    /**
     * @test
     */
    public function toArray_returns_arrayable()
    {
        $data = ['foo' => 'bar', 'a' => 'b', 'all' => [1,2,3]];
        $source = $this->make($data);
        $array = $this->make($source);
        $this->assertSame($data, $array->toArray());
    }

    /**
     * @test
     */
    public function toArray_returns_iterable()
    {
        $source = ['foo' => 'bar', 'a' => 'b', 'all' => [1,2,3]];
        $array = $this->make(new ArrayObject($source));
        $this->assertSame($source, $array->toArray());
    }

    /**
     * @test
     */
    public function clear_clears_on_array()
    {
        $source = ['foo' => 'bar', 'a' => 'b', 'all' => [1,2,3]];
        $array = $this->make($source);
        $array->clear();
        $this->assertEmpty($array->toArray());
    }

    /**
     * @test
     */
    public function clear_clears_on_ArrayData()
    {
        $source = ['foo' => 'bar', 'a' => 'b', 'all' => [1,2,3]];
        $array = $this->make($this->make($source));
        $array->clear();
        $this->assertEmpty($array->toArray());
    }

    /**
     * @test
     */
    public function clear_clears_selected_keys()
    {
        $source = ['foo' => 'bar', 'a' => 'b', 'all' => [1,2,3]];
        $array = $this->make($source);
        $array->clear(['a']);
        $this->assertTrue(isset($array['foo']));
        $this->assertFalse(isset($array['a']));
    }

    /**
     * @test
     */
    public function clear_clears_nothing_with_empty_array()
    {
        $source = ['foo' => 'bar', 'a' => 'b', 'all' => [1,2,3]];
        $array = $this->make($source);
        $array->clear([]);
        $this->assertSame($source, $array->toArray());
    }

    /**
     * @test
     */
    public function filter_filters_exact_matches()
    {
        $source = [
            (object)['id' => 1, 'first_name' => 'Maria', 'last_name' => 'Tunningham', 'tags' => ['young','female']],
            (object)['id' => 2, 'first_name' => 'Marc', 'last_name' => 'I marc', 'tags' => ['young','male']],
            (object)['id' => 3, 'first_name' => 'Manon', 'last_name' => 'Off', 'tags' => ['young','female']],
        ];
        $array = $this->make($source)->disableFuzzySearch();
        $this->assertFalse($array->isFuzzySearchEnabled());
        $this->assertEquals([$source[2]], $array->filter('first_name', 'Manon')->toArray());

        $this->assertEquals([$source[1]], $array->filter('tags', ['young','male'])->toArray());
    }

    /**
     * @test
     */
    public function filter_filters_fuzzy_matches()
    {
        $source = [
            (object)['id' => 1, 'first_name' => 'Maria', 'last_name' => 'Tunningham', 'tags' => ['young','female']],
            (object)['id' => 2, 'first_name' => 'Marc', 'last_name' => 'I marc', 'tags' => ['young','male']],
            (object)['id' => 3, 'first_name' => 'Manon', 'last_name' => 'Off', 'tags' => ['young','female']],
        ];
        $array = $this->make($source)->enableFuzzySearch();
        $this->assertTrue($array->isFuzzySearchEnabled());
        $this->assertEquals([$source[2]], $array->filter('first_name', 'Man?n')->toArray());

        $this->assertEquals($source, $array->filter('first_name', 'ma*')->toArray());
    }

    protected function make($source=[]) : FilterableArray
    {
        return new FilterableArray($source);
    }
}
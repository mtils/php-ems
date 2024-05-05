<?php

namespace Ems\Core;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Ems\Testing\LoggingCallable;
use Ems\Core\Support\ArrayAccessMethods;
use Ems\Core\Support\IteratorAggregateMethods;
use stdClass;

class HelperTest extends \Ems\TestCase
{
    public function test_call_calls_callable()
    {
        $callable = new LoggingCallable;
        Helper::call($callable);
        $this->assertCount(1, $callable);

        $callable = new LoggingCallable;
        Helper::call($callable, 'a');
        $this->assertCount(1, $callable);
        $this->assertEquals('a', $callable->arg(0));

        $callable = new LoggingCallable;
        Helper::call($callable, ['a', 'b']);
        $this->assertCount(1, $callable);
        $this->assertEquals('a', $callable->arg(0));
        $this->assertEquals('b', $callable->arg(1));

        $callable = new LoggingCallable;
        Helper::call($callable, ['a', 'b', 'c']);
        $this->assertCount(1, $callable);
        $this->assertEquals('a', $callable->arg(0));
        $this->assertEquals('b', $callable->arg(1));
        $this->assertEquals('c', $callable->arg(2));

        $callable = new LoggingCallable;
        Helper::call($callable, ['a', 'b', 'c', 'd']);
        $this->assertCount(1, $callable);
        $this->assertEquals('a', $callable->arg(0));
        $this->assertEquals('b', $callable->arg(1));
        $this->assertEquals('c', $callable->arg(2));
        $this->assertEquals('d', $callable->arg(3));

        $callable = new LoggingCallable;
        Helper::call($callable, ['a', 'b', 'c', 'd', 'e']);
        $this->assertCount(1, $callable);
        $this->assertEquals('a', $callable->arg(0));
        $this->assertEquals('b', $callable->arg(1));
        $this->assertEquals('c', $callable->arg(2));
        $this->assertEquals('d', $callable->arg(3));
        $this->assertEquals('e', $callable->arg(4));

    }

    public function test_camelCase_does_camelCase()
    {

        $tests = [
            'get'                   => 'get',
            'get-user'              => 'getUser',
            'get-user-and-sisters'  => 'getUserAndSisters'
        ];

        foreach ($tests as $snake_case=>$camelCase) {
            $this->assertEquals($camelCase, Helper::camelCase($snake_case));
            $this->assertEquals($camelCase, Helper::camelCase(str_replace('-','_',$snake_case)));
        }
    }

    public function test_studlyCaps_does_studlyCaps()
    {

        $tests = [
            'user'                              => 'User',
            'user-category'                     => 'UserCategory',
            'abstract-user-category-provider'   => 'AbstractUserCategoryProvider'
        ];

        foreach ($tests as $snake_case=>$camelCase) {
            $this->assertEquals($camelCase, Helper::studlyCaps($snake_case));
            $this->assertEquals($camelCase, Helper::studlyCaps(str_replace('-','_',$snake_case)));
        }
    }

    public function test_snake_case_does_snake_case()
    {

        $tests = [
            'get'                   => 'get',
            'get-user'              => 'getUser',
            'get-user-and-sisters'  => 'getUserAndSisters'
        ];

        foreach ($tests as $snake_case=>$camelCase) {
            $this->assertEquals($snake_case, Helper::snake_case($camelCase, '-'));
            $this->assertEquals($snake_case, Helper::snake_case($camelCase, '-'));
            $this->assertEquals(str_replace('-','_',$snake_case), Helper::snake_case($camelCase));
        }
    }

    public function test_without_namespace_cuts_namespace_from_classname()
    {
        $tests = [
            'User'          => 'User',
            'App\Project'   => 'Project',
            get_class()     => 'HelperTest'
        ];

        foreach ($tests as $full=>$class) {
            $this->assertEquals($class, Helper::withoutNamespace($full));
        }

    }

    public function test_rtrimWord_cuts_()
    {
        $this->assertEquals('Ham and ', Helper::rtrimWord('Ham and Eggs', 'Eggs'));
        $this->assertEquals('Rebecca', Helper::rtrimWord('Rebecca and Jill', ' and Jill'));
        $this->assertEquals('House', Helper::rtrimWord('HouseParty', 'Party'));
        $this->assertEquals('Does not contain word', Helper::rtrimWord('Does not contain word', 'Party'));
    }

    public function test_isSequential_returns_true_on_empty_array()
    {
        $this->assertTrue(Helper::isSequential([]));
    }

    public function test_isSequential_returns_true_on_array_with_single_value()
    {
        $this->assertTrue(Helper::isSequential(['a']));
    }

    public function test_isSequential_returns_false_on_string()
    {
        $this->assertFalse(Helper::isSequential('foo'));
    }

    public function test_isSequential_returns_false_on_arbitary_object()
    {
        $this->assertFalse(Helper::isSequential(new \stdClass));
    }

    public function test_isSequential_returns_false_on_ArrayAccess_object()
    {
        $test = new HelperTestArrayAccess;
        $test[0] = 'foo';
        $test[1] = 'bar';
        $this->assertFalse(Helper::isSequential($test));
    }

    public function test_isSequential_returns_true_on_Traversable_object()
    {
        $test = new HelperTestArray;
        $test[0] = 'foo';
        $test[1] = 'bar';
        $this->assertTrue(Helper::isSequential($test));
    }

    public function test_isSequential_returns_false_on_object_with_non_zero_first_key()
    {
        $test = new HelperTestArray;
        $test[23] = 'foo';
        $test[1] = 'bar';
        $this->assertFalse(Helper::isSequential($test));
    }

    public function test_isSequential_returns_true_on_empty_traversable_object()
    {
        $this->assertTrue(Helper::isSequential(new HelperTestArray));
    }

    public function test_isSequential_with_strict_returns_true_on_numeric_array()
    {
        $this->assertTrue(Helper::isSequential(['a', 'b', 'c'], true));
    }

    public function test_isSequential_with_strict_returns_false_on_nearly_numeric_array()
    {
        $test = [
            0 => 'a',
            1 => 'b',
            3 => 'd'
        ];
        $this->assertFalse(Helper::isSequential($test, true));
    }

    public function test_isSequential_with_strict_returns_true_on_numeric_object()
    {
        $test = new HelperTestArray;
        $test[0] = 'a';
        $test[1] = 'b';
        $test[2] = 'c';
        $test[3] = 'd';
        $this->assertTrue(Helper::isSequential($test, true));
    }

    public function test_isSequential_with_strict_returns_false_on_nearly_numeric_object()
    {
        $test = new HelperTestArray;
        $test[0] = 'a';
        $test[1] = 'b';
        $test[2] = 'c';
        $test[4] = 'e';
        $this->assertFalse(Helper::isSequential($test, true));
    }

    public function test_typeName_returns_gettype_if_value_no_object()
    {
        $tests = [
            true, false, 1.2, 13, [], 'foo'
        ];
        foreach ($tests as $test) {
            $this->assertEquals(gettype($test), Helper::typeName($test));
        }
        $this->assertEquals('null', Helper::typeName(null));

        $this->assertEquals('stdClass', Helper::typeName(new \stdClass));
        $this->assertEquals(get_class($this), Helper::typeName($this));
    }

    public function test_keys_returns_empty_array_on_unsupported_types()
    {
        $tests = [
            true, false, 1.2, 13, 'foo'
        ];
        foreach ($tests as $test) {
            $this->assertEquals([], Helper::keys($test));
        }
    }

    public function test_keys_returns_properties_of_not_Traversable_object()
    {
        $array = [
            'id' => 15,
            'name' => 'peter',
            'age' => 15.3
        ];

        $test = (object)$array;

        $this->assertEquals(array_keys($array), Helper::keys($test));

    }

    public function test_first_returns_first_array_value()
    {
        $this->assertEquals(12, Helper::first([12]));
    }

    public function test_first_returns_null_on_empty_array()
    {
        $this->assertNull(Helper::first([]));
    }

    public function test_first_returns_first_char_of_string()
    {
        $this->assertEquals('a', Helper::first('abcde'));
        $this->assertNull(Helper::first(''));
    }

    public function test_first_returns_null_on_unsupported_values()
    {
        $this->assertNull(Helper::first(null));
        $this->assertNull(Helper::first(12));
        $this->assertNull(Helper::first(3.5));
        $this->assertNull(Helper::first(true));
    }

    public function test_first_returns_first_on_ArrayAccess_object()
    {

        $test = new HelperTestArray;
        $test[0] = 'foo';
        $this->assertEquals('foo', Helper::first($test));

        $test = new HelperTestArray;
        $this->assertNull(Helper::first($test));
    }

    public function test_first_returns_first_on_Traversable_object()
    {

        $test = new HelperTestTraversable;
        $test->array = ['foo'];
        $this->assertEquals('foo', Helper::first($test));

        $test = new HelperTestTraversable;
        $this->assertNull(Helper::first($test));

    }

    public function test_first_returns_null_first_on_not_Traversable_object()
    {
        $this->assertNull(Helper::first((object)['first'=>'foo']));
    }

    public function test_last_returns_last_array_value()
    {
        $this->assertEquals(12, Helper::last([12]));
    }

    public function test_last_returns_null_on_empty_array()
    {
        $this->assertNull(Helper::last([]));
    }

    public function test_last_returns_last_char_of_string()
    {
        $this->assertEquals('e', Helper::last('abcde'));
        $this->assertEquals('ü', Helper::last('abcdeääßü'));
        $this->assertNull(Helper::last(''));
    }

    public function test_last_returns_null_on_unsupported_values()
    {
        $this->assertNull(Helper::last(null));
        $this->assertNull(Helper::last(12));
        $this->assertNull(Helper::last(3.5));
        $this->assertNull(Helper::last(true));
        $this->assertNull(Helper::last(new stdClass()));
    }

    public function test_last_returns_last_on_Traversable_object()
    {

        $test = new HelperTestTraversable;
        $test->array = ['foo', 'bar'];
        $this->assertEquals('bar', Helper::last($test));

        $test = new HelperTestTraversable;
        $this->assertNull(Helper::last($test));

    }

    public function test_stringSplit_splits_normal_string()
    {
        $test = 'Hello my name is michael';
        $this->assertEquals(str_split($test), Helper::stringSplit($test));
        $this->assertEquals(str_split($test,3), Helper::stringSplit($test,3));
    }

    public function test_stringSplit_splits_unicode_string()
    {
        $test = 'Süße Möhrchen';

        $result = [
            'S','ü','ß','e',' ','M','ö','h','r','c','h','e','n'
        ];

        $result3 = [
            'Süß','e M','öhr','che','n'
        ];

        $this->assertEquals($result, Helper::stringSplit($test));
        $this->assertEquals($result3, Helper::stringSplit($test,3));
    }

    public function test_startsWith_works_with_strings()
    {
        $this->assertTrue(Helper::startsWith('Hello', 'H'));
        $this->assertTrue(Helper::startsWith('Hello', 'Hell'));
        $this->assertTrue(Helper::startsWith('Hello', 'Hello'));
        $this->assertFalse(Helper::startsWith('Hello', 'e'));

        $this->assertTrue(Helper::startsWith('Ährenöbst', 'Ä'));
        $this->assertTrue(Helper::startsWith('Ährenöbst', 'Ährenö'));
        $this->assertFalse(Helper::startsWith('Ährenöbst', 'Ühr'));

    }

    public function test_startsWith_works_with_numbers()
    {
        $this->assertTrue(Helper::startsWith(1, 1));
        $this->assertTrue(Helper::startsWith(1.53, 1));
        $this->assertTrue(Helper::startsWith(3529, 35));
        $this->assertFalse(Helper::startsWith(3529, 36));

    }

    public function test_startsWith_works_with_arrays()
    {
        $this->assertTrue(Helper::startsWith(['a','b','c'], 'a'));
        $this->assertTrue(Helper::startsWith(['bananas','apples','melon'], 'bananas'));
        $this->assertFalse(Helper::startsWith(['bananas','apples','melon'], 'apples'));
    }

    public function test_endsWith_works_with_strings()
    {
        $this->assertTrue(Helper::endsWith('Hello', 'o'));
        $this->assertTrue(Helper::endsWith('Hello', 'ello'));
        $this->assertTrue(Helper::endsWith('Hello', 'Hello'));
        $this->assertFalse(Helper::endsWith('Hello', 'l'));

        $this->assertTrue(Helper::endsWith('Ährenöbstä', 'ä'));
        $this->assertTrue(Helper::endsWith('Ährenöbstä', 'öbstä'));
        $this->assertFalse(Helper::endsWith('Ährenöbst', 'Ühr'));

    }

    public function test_endsWith_works_with_numbers()
    {
        $this->assertTrue(Helper::endsWith(1, 1));
        $this->assertTrue(Helper::endsWith(1.53, 53));
        $this->assertTrue(Helper::endsWith(3529, 29));
        $this->assertFalse(Helper::endsWith(3529, 35));

    }

    public function test_endsWith_works_with_arrays()
    {
        $this->assertTrue(Helper::endsWith(['a','b','c'], 'c'));
        $this->assertTrue(Helper::endsWith(['bananas','apples','melon'], 'melon'));
        $this->assertFalse(Helper::endsWith(['bananas','apples','melon'], 'apples'));
    }

    public function test_value_uses_extractor()
    {
        $test = ['a'=>['b'=>'c']];
        $this->assertEquals('c', Helper::value($test, 'a.b'));
    }

    public function test_setExtractor()
    {

        $extractor = new Extractor;

        $mock = $this->mock(Extractor::class);

        Helper::setExtractor($mock);

        $mock->shouldReceive('value')
             ->with('a', 'b')
             ->atLeast()->once()
             ->andReturn('c');

        $this->assertEquals('c', Helper::value('a', 'b'));

        // Better reset it, its static
        Helper::setExtractor($extractor);
    }

    public function test_dump()
    {
        $this->assertStringContainsString("int", Helper::dump(42));
        $this->assertStringContainsString("42", Helper::dump(42));
    }
}

class HelperTestArrayAccess implements ArrayAccess
{
    use ArrayAccessMethods;
}

class HelperTestArray implements ArrayAccess, IteratorAggregate
{
    use ArrayAccessMethods;
    use IteratorAggregateMethods;
}

class HelperTestTraversable implements IteratorAggregate
{
    public $array = [];

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->array);
    }
}

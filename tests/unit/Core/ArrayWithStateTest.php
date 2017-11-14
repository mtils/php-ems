<?php

namespace Ems\Core;

use Ems\Contracts\Core\ArrayWithState as ArrayWithStateContract;
use Ems\Core\Support\PublicAttributeFilling;
use Ems\Testing\LoggingCallable;

class ArrayWithStateTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            ArrayWithStateContract::class,
            $this->newArray()
        );
    }

    public function test_offsetExists_returns_false_if_missing()
    {
        $array = $this->newArray();
        $this->assertFalse(isset($array['foo']));
    }

    public function test_offsetExists_returns_true_if_setted()
    {
        $array = $this->newArray();
        $array['foo'] = true;
        $this->assertTrue(isset($array['foo']));
    }

    public function test_offsetGet_returns_value_if_setted()
    {
        $array = $this->newArray();
        $array['foo'] = 'bar';
        $this->assertEquals('bar', $array['foo']);
    }

    public function test_offsetUnset_removes_value()
    {
        $array = $this->newArray();
        $array['foo'] = 'bar';
        $this->assertTrue(isset($array['foo']));
        unset($array['foo']);
        $this->assertFalse(isset($array['foo']));
    }

    public function test_keys_returns_array_keys()
    {

        $array = $this->newArray();
        $array['foo'] = 'bar';
        $array['bar'] = 'baz';

        $this->assertEquals(['foo', 'bar'], $array->keys()->getSource());
    }

    public function test_toArray_returns_setted_array()
    {

        $awaited = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        $array = $this->newArray();

        foreach ($awaited as $key=>$value) {
            $array[$key] = $value;
        }

        $this->assertEquals($awaited, $array->toArray());
    }

    public function test_fill_by_provider()
    {

        $awaited = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        $filler = new LoggingCallable(function ($array) use ($awaited) {
            return $array->fill($awaited);
        });

        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $this->assertEquals($awaited, $array->toArray());

    }

    public function test_fill_by_not_existing_provider()
    {

        $awaited = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        $array = $this->newFillableArray();
        $array->defaultAttributes = $awaited;

        $this->assertEquals($awaited, $array->toArray());

    }

    public function test_isNew_returns_true_if_new()
    {
        $this->assertTrue($this->newArray()->isNew());
    }

    public function test_isNew_returns_false_if_filled()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $this->assertFalse($array->isNew());
    }

    public function test_wasModified_returns_false_without_parameters()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $this->assertFalse($array->wasModified());
    }

    public function test_wasModified_returns_true_without_parameters()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $array['foo'] = 'blink';

        $this->assertTrue($array->wasModified());
    }

    public function test_wasModified_returns_false_with_parameters()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $array['foo'] = 'blink';

        $this->assertFalse($array->wasModified('bar'));
    }

    public function test_wasModified_returns_true_with_parameters()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $array['foo'] = 'blink';

        $this->assertTrue($array->wasModified('foo'));
    }

    public function test_wasModified_returns_true_with_parameters_if_original_key_didnt_exist()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $array['new'] = 'bub';

        $this->assertTrue($array->wasModified('new'));
    }

    public function test_wasModified_without_parameters_returns_true_if_original_key_was_deleted_in_attributes()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        unset($array['bar']);

        $this->assertTrue($array->wasModified());
    }

    public function test_wasModified_returns_true_if_original_key_was_deleted_in_attributes()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        unset($array['bar']);

        $this->assertTrue($array->wasModified('bar'));
    }

    public function test_wasLoaded_returns_true_without_parameters()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $this->assertTrue($array->wasLoaded());
    }

    public function test_wasLoaded_returns_false_with_parameters()
    {
        $array = $this->newFillableArray();
        $array->defaultAttributes = ['hihi' => 'hahaha'];
        $this->assertFalse($array->wasLoaded('hihi'));
    }

    public function test_wasLoaded_returns_true_with_parameters()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        foreach (['foo', 'bar'] as $key) {
            $this->assertTrue($array->wasLoaded($key));
        }

        $this->assertTrue($array->wasLoaded('foo', 'bar'));
        $this->assertTrue($array->wasLoaded(['foo', 'bar']));
    }

    public function test_wasLoaded_returns_false_with_unknown_parameters()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        foreach (['acme', 'baz'] as $key) {
            $this->assertFalse($array->wasLoaded($key));
        }
    }

    public function test_wasLoaded_returns_false_with_setted_parameters()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $array['acme'] = 'hihi';

        foreach (['acme', 'baz'] as $key) {
            $this->assertFalse($array->wasLoaded($key));
        }
    }

    public function test_getOriginal_without_parameters_returns_original_values()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $defaults = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        $this->assertEquals($defaults, $array->getOriginal());
    }

    public function test_getOriginal_with_parameters_returns_original_values()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $this->assertEquals('bar', $array->getOriginal('foo'));
        $this->assertEquals('baz', $array->getOriginal('bar'));

        $array['foo'] = 'fii';
        $array['bar'] = 'boo';

        $this->assertEquals('bar', $array->getOriginal('foo'));
        $this->assertEquals('baz', $array->getOriginal('bar'));

        $this->assertEquals([
            'foo' => 'fii',
            'bar' => 'boo'
        ], $array->toArray());

    }

    public function test_getOriginal_with_parameters_returns_default_if_original_not_setted()
    {
        $filler = $this->newFiller();
        $array = $this->newFillableArray()->autoAssignAttributesBy($filler);

        $this->assertEquals('blong', $array->getOriginal('bling', 'blong'));
    }

    public function newArray()
    {
        return new ArrayWithState;
    }

    public function newTestArray()
    {
        return new ArrayWithStateTestArray;
    }

    public function newFillableArray()
    {
        return new ArrayWithStateTestFillable;
    }

    public function newFiller($defaults=null)
    {
        $defaults = $defaults ?: [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        return new LoggingCallable(function (ArrayWithStateTestFillable $array) use ($defaults) {
            return $array->fill($defaults);
        });
    }
}

class ArrayWithStateTestArray extends ArrayWithState
{

    public $lastArgs = [];
    public $method;
    public $initialAttributes = [];

    public function __construct(array $initialAttributes=[])
    {
        $this->initialAttributes = $initialAttributes;
    }

}

class ArrayWithStateTestFillable extends ArrayWithState
{
    use PublicAttributeFilling;

    public $defaultAttributes = [];
}

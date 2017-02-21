<?php

namespace Ems\XType;

use DateTime;
use Ems\Contracts\XType\XType;
use Ems\Contracts\XType\TypeFactory as TypeFactoryContract;
use Ems\Core\Collections\OrderedList;

class TemplateTypeFactoryTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            TypeFactoryContract::class,
            $this->newFactory()
        );
    }

    public function test_canCreate_returns_always_true()
    {
        $factory = $this->newFactory();
        foreach ([1, 'a', null, [],new \stdclass] as $test) {
            $this->assertTrue($factory->canCreate($test));
        }
    }

    public function test_toType_converts_bool()
    {

        $factory = $this->newFactory();

        $this->assertInstanceOf(BoolType::class, $factory->toType(true));
        $this->assertEquals(true, $factory->toType(true)->defaultValue);
        $this->assertInstanceOf(BoolType::class, $factory->toType(false));
        $this->assertEquals(false, $factory->toType(false)->defaultValue);

    }

    public function test_toType_converts_int()
    {

        $factory = $this->newFactory();

        $type = $factory->toType(12);

        $this->assertInstanceOf(NumberType::class, $type);
        $this->assertEquals(12, $type->defaultValue);
        $this->assertEquals('int', $type->nativeType);

    }

    public function test_toType_converts_float()
    {

        $factory = $this->newFactory();

        $type = $factory->toType(12.3);

        $this->assertInstanceOf(NumberType::class, $type);
        $this->assertEquals(12.3, $type->defaultValue);
        $this->assertEquals('float', $type->nativeType);

    }

    public function test_toType_converts_string()
    {

        $factory = $this->newFactory();

        $type = $factory->toType('bar');

        $this->assertInstanceOf(StringType::class, $type);
        $this->assertEquals('bar', $type->defaultValue);

    }

    public function test_toType_converts_null()
    {

        $factory = $this->newFactory();

        $type = $factory->toType(null);

        $this->assertInstanceOf(StringType::class, $type);
        $this->assertNull($type->defaultValue);

    }

    public function test_toType_converts_DateTime()
    {

        $factory = $this->newFactory();

        $time = new DateTime('2016-12-24 20:03:15');

        $type = $factory->toType($time);

        $this->assertInstanceOf(TemporalType::class, $type);
        $this->assertEquals($time, $type->defaultValue);

    }

    public function test_toType_converts_numeric_array()
    {

        $factory = $this->newFactory();

        $data = ['a', 'b', 'c', 'd'];

        $type = $factory->toType($data);

        $this->assertInstanceOf(SequenceType::class, $type);
        $this->assertEquals($data, $type->defaultValue);
        $this->assertInstanceOf(StringType::class, $type->itemType);

        $type = $factory->toType([]);

        $this->assertInstanceOf(SequenceType::class, $type);
        $this->assertEquals([], $type->defaultValue);
        $this->assertInstanceOf(StringType::class, $type->itemType);

    }

    public function test_toType_converts_sequential_object()
    {

        $factory = $this->newFactory();

        $data = new OrderedList(['a', 'b', 'c', 'd']);

        $type = $factory->toType($data);

        $this->assertInstanceOf(SequenceType::class, $type);
        $this->assertEquals($data, $type->defaultValue);
        $this->assertInstanceOf(StringType::class, $type->itemType);

        $type = $factory->toType(new OrderedList([]));

        $this->assertInstanceOf(SequenceType::class, $type);
        $this->assertEquals([], $type->defaultValue->getSource());
        $this->assertInstanceOf(StringType::class, $type->itemType);

    }

    public function test_toType_does_not_cast_to_sequence_if_indexes_are_not_in_order()
    {

        $factory = $this->newFactory();

        $data = [ 0 => 'a', 1 => 'b', 3 => 'c', 4 => 'd'];

        $type = $factory->toType($data);

        $this->assertFalse($type instanceof SequenceType);

    }

    public function test_toType_does_not_cast_to_sequence_if_non_numeric_keys()
    {

        $factory = $this->newFactory();

        $data = [ 'a' => 0, 'b' => 1, 'c' => 2, 'd' => 3];

        $type = $factory->toType($data);

        $this->assertFalse($type instanceof SequenceType);

    }

    public function test_toType_does_not_cast_to_sequence_if_item_types_differ()
    {

        $factory = $this->newFactory();

        $data = ['a', 'b', 'c', 'd', 45];

        $type = $factory->toType($data);

        $this->assertFalse($type instanceof SequenceType);

    }

    public function test_toType_does_converts_object()
    {

        $factory = $this->newFactory();

        $data = (object)['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3];

        $type = $factory->toType($data);

        $this->assertInstanceOf(ObjectType::class, $type);

    }

    protected function newFactory()
    {
        return new TemplateTypeFactory;
    }

}

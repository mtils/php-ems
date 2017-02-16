<?php

namespace Ems\XType;

use Ems\Contracts\XType\XType;
use Ems\Contracts\XType\TypeFactory as TypeFactoryContract;

class TypeFactoryTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            TypeFactoryContract::class,
            $this->newFactory()
        );
    }

    public function test_canCreate_returns_true_on_string()
    {
        $factory = $this->newFactory();
        $this->assertTrue($factory->canCreate('number|min:5'));
    }

    public function test_canCreate_returns_false_on_int()
    {
        $factory = $this->newFactory();
        $this->assertFalse($factory->canCreate(15));
    }

    public function test_canCreate_returns_true_on_associative_array()
    {
        $factory = $this->newFactory();
        $this->assertTrue($factory->canCreate(['id'=>'numeric|max:5000']));
    }

    public function test_canCreate_returns_false_on_indexed_array()
    {
        $factory = $this->newFactory();
        $this->assertFalse($factory->canCreate(['id','false']));
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_toType_throws_InvalidArgumentException_if_type_is_not_supported()
    {
        $factory = $this->newFactory();
        $factory->toType(15);
    }

    public function test_factory_creates_NumberType()
    {
        $type = $this->newFactory()->toType('number');
        $this->assertInstanceOf(NumberType::class, $type);
    }

    public function test_factory_creates_NumberType_with_cache()
    {
        $factory = $this->newFactory();
        $type = $factory->toType('number');
        $this->assertInstanceOf(NumberType::class, $type);
        $type = $factory->toType('number');
        $this->assertInstanceOf(NumberType::class, $type);
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\NotFound
     **/
    public function test_factory_throws_NotFound_if_class_not_found()
    {
        $this->newFactory()->toType('foo');
    }

    public function test_factory_creates_NumberType_with_properties()
    {
        $type = $this->newFactory()->toType('number|min:15|max:2000');
        $this->assertInstanceOf(NumberType::class, $type);
        $this->assertEquals(15, $type->min);
        $this->assertEquals(2000, $type->max);
    }

    public function test_factory_creates_NumberType_with_valueless_properties()
    {
        $type = $this->newFactory()->toType('number|min:15|max:2000|required');
        $this->assertInstanceOf(NumberType::class, $type);
        $this->assertEquals(15, $type->min);
        $this->assertEquals(2000, $type->max);
        $this->assertFalse($type->canBeNull);
    }

    public function test_factory_creates_NumberType_with_valueless_inversed_properties()
    {
        $type = $this->newFactory()->toType('number|min:15|max:2000|!required');
        $this->assertInstanceOf(NumberType::class, $type);
        $this->assertEquals(15, $type->min);
        $this->assertEquals(2000, $type->max);
        $this->assertTrue($type->canBeNull);
    }

    public function test_factory_creates_ArrayAccessType()
    {

        $config = [
            'id'    => 'number|min:15|max:2000|!required',
            'login' => 'string|min:3|max:255|required'
        ];

        $type = $this->newFactory()->toType($config);

        $this->assertInstanceOf(ArrayAccessType::class, $type);

        $idType = $type['id'];

        $this->assertInstanceOf(NumberType::class, $idType);
        $this->assertEquals(15, $idType->min);
        $this->assertEquals(2000, $idType->max);
        $this->assertTrue($idType->canBeNull);

        $loginType = $type['login'];

        $this->assertInstanceOf(StringType::class, $loginType);
        $this->assertEquals(3, $loginType->min);
        $this->assertEquals(255, $loginType->max);
        $this->assertFalse($loginType->canBeNull);

    }

    public function test_factory_creates_type_by_extension()
    {
        $factory = $this->newFactory();
        $factory->extend('email', function($typeName, $factory) {
            return new StringType(['min'=>3, 'max'=>254]);
        });

        $type = $factory->toType('email');

        $this->assertInstanceOf(StringType::class, $type);
        $this->assertEquals(3, $type->min);
        $this->assertEquals(254, $type->max);
        $this->assertEquals('email', $type->getName());
    }

    public function test_factory_creates_type_in_other_namespace()
    {
        $factory = $this->newFactory();

        $type = $factory->toType('area');

        $this->assertInstanceOf(UnitTypes\AreaType::class, $type);
        $this->assertEquals('area', $type->getName());
    }

    public function test_factory_overwrites_properties_from_extension_type()
    {
        $factory = $this->newFactory();
        $factory->extend('email', function($typeName, $factory) {
            return new StringType(['min'=>3, 'max'=>254]);
        });

        $type = $factory->toType('email|min:10|max:128');

        $this->assertInstanceOf(StringType::class, $type);
        $this->assertEquals(10, $type->min);
        $this->assertEquals(128, $type->max);
        $this->assertEquals('email', $type->getName());
    }

    protected function newFactory()
    {
        return new TypeFactory;
    }

}

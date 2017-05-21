<?php

namespace Ems\XType;

use Ems\Contracts\XType\XType;
use Ems\Contracts\XType\SelfExplanatory;
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

    public function test_factory_creates_SequenceType_of_numbers()
    {

        $config = [
            'id'    => 'number|min:15|max:2000|!required',
            'login' => 'string|min:3|max:255|required'
        ];

        $config = 'sequence|min:1|max:15|itemType:[number|min:1990|max:2017|required]';

        $type = $this->newFactory()->toType($config);

        $this->assertInstanceOf(SequenceType::class, $type);
        $this->assertEquals(1, $type->min);
        $this->assertEquals(15, $type->max);

        $itemType = $type->itemType;

        $this->assertInstanceOf(NumberType::class, $itemType);
        $this->assertEquals(1990, $itemType->min);
        $this->assertEquals(2017, $itemType->max);
        $this->assertFalse($itemType->canBeNull);

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

    public function test_factory_creates_type_by_SelfExplanatory()
    {
        $factory = $this->newFactory();

        $user = new TypeFactoryTest_User;

        $type = $factory->toType($user);

        $this->assertInstanceOf(ObjectType::class, $type);

        $idType = $type['id'];
        $this->assertInstanceOf(NumberType::class, $idType);
        $this->assertEquals(1, $idType->min);
        $this->assertEquals(5000, $idType->max);

    }

    public function test_factory_creates_type_by_SelfExplanatory_nested()
    {
        $factory = $this->newFactory();

        $user = new TypeFactoryTest_User;

        $type = $factory->toType($user);

        $this->assertInstanceOf(ObjectType::class, $type);

        $cityType = $type['address']['city'];
        $this->assertInstanceOf(StringType::class, $cityType);
        $this->assertEquals(2, $cityType->min);
        $this->assertEquals(255, $cityType->max);

        $countryCodeType = $type['address']['country']['iso_code'];
        $this->assertInstanceOf(StringType::class, $countryCodeType);
        $this->assertEquals(2, $countryCodeType->min);
        $this->assertEquals(2, $countryCodeType->max);

        $tagsType = $type['tags'];
        $this->assertInstanceOf(SequenceType::class, $tagsType);
        $tagType = $type['tags']->itemType['name'];

    }

    public function test_factory_just_returns_type_if_myXType_returns_XType()
    {
        $factory = $this->newFactory();

        $foo = new TypeFactoryTest_Manual;

        $type = $factory->toType($foo);

        $this->assertInstanceOf(ObjectType::class, $type);
    }

    protected function newFactory()
    {
        return new TypeFactory;
    }

}

abstract class TypeFactoryTest_Model implements SelfExplanatory
{

    public function xTypeConfig()
    {

        $translated = [];

        foreach ($this->xType as $key=>$rule) {
            if ($this->isForeignObject($rule)) {
                $translated[$key] = $this->replaceClass($rule);
                continue;
            }
            if ($this->isSequence($rule)) {
                $translated[$key] = $this->replaceSequenceClass($rule);
                continue;
            }
            $translated[$key] = $rule;
        }

        return $translated;
    }

    protected function replaceClass($rule)
    {
        list($start, $class) = explode(':', $rule);
        $fullyQualified = __NAMESPACE__ . '\TypeFactoryTest_' . $class;
        return "$start:$fullyQualified";
    }

    protected function replaceSequenceClass($rule)
    {
        list($start, $class) = explode('class:', $rule);
        $class = trim($class, ']');
        $fullyQualified = __NAMESPACE__ . '\TypeFactoryTest_' . $class;

        return $start . "class:$fullyQualified]";
    }

    protected function isForeignObject($rule)
    {
        return strpos($rule, 'object|class:') === 0;
    }

    protected function isSequence($rule)
    {
        return strpos($rule, 'sequence|itemType:') === 0;
    }
}


class TypeFactoryTest_User extends TypeFactoryTest_Model
{
    protected $xType = [
        'id'        => 'number|min:1|max:5000',
        'login'     => 'string|min:5|max:255',
        'address'   => 'object|class:Address',
        'tags'      => 'sequence|itemType:[object|class:Tag]'
    ];
}

class TypeFactoryTest_Address extends TypeFactoryTest_Model
{
    protected $xType = [
        'id'        => 'number|min:1|max:5000',
        'city'      => 'string|min:2|max:255',
        'country'   => 'object|class:Country'
    ];
}

class TypeFactoryTest_Country extends TypeFactoryTest_Model
{
    protected $xType = [
        'id'        => 'number|min:1|max:5000',
        'name'      => 'string|min:2|max:255',
        'iso_code'  => 'string|min:2|max:2'
    ];
}

class TypeFactoryTest_Tag extends TypeFactoryTest_Model
{
    protected $xType = [
        'id'        => 'number|min:1|max:5000',
        'name'      => 'string|min:5|max:128'
    ];
}

class TypeFactoryTest_Manual implements SelfExplanatory
{

    public function xTypeConfig()
    {
        return new ObjectType;
    }
}

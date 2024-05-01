<?php

namespace Ems\XType;

use BadMethodCallException;
use Ems\Contracts\Core\Errors\NotFound;
use Ems\Contracts\XType\XType;
use Ems\Validation\Rule;

class AbstractTypeTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(XType::class, self::newType());
    }

    public function test_getName_returns_calculated_name()
    {
        $type = $this->newType();
        $this->assertNotEmpty($type->getName());
    }

    public function test_getName_returns_setted_name()
    {
        $type = $this->newType();
        $type->setName('foo');
        $this->assertEquals('foo', $type->getName());
    }

    public function test_name_property_returns_setted_name()
    {
        $type = $this->newType();
        $type->name = 'foo';
        $this->assertEquals('foo', $type->name);
    }

    public function test_getConstraint_and_setConstraints()
    {
        $constraint = new Rule;
        $type = $this->newType();
        $this->assertSame($type, $type->setConstraints($constraint));
        $this->assertSame($constraint, $type->getConstraints());
    }

    public function test_fill_fills_canBeNull_with_all_aliases()
    {
        $type = $this->newType();

        $this->assertFalse(isset($type->notNull));

        $type->fill(['canBeNull'=> false]);

        $this->assertTrue($type->notNull);
        $this->assertTrue($type->constraints->notNull);

        $type->fill(['canBeNull'=> true]);

        $this->assertFalse(isset($type->notNull));


        $type->fill(['required'=> true]);

        $this->assertTrue($type->notNull);
        $this->assertTrue($type->not_null);

        $type->fill(['required'=> false]);

        $this->assertFalse(isset($type->notNull));

        $type->fill(['null'=> false]);

        $this->assertTrue($type->notNull);
        $this->assertTrue($type->not_null);


        $type->fill(['null'=> true]);

        $this->assertFalse(isset($type->notNull));

        $type->fill(['optional'=> false]);

        $this->assertTrue($type->notNull);
        $this->assertTrue($type->not_null);
    }

    public function test_fill_fills_readonly_with_all_aliases()
    {
        $type = $this->newType();

        $type->fill(['readonly'=> true]);

        $this->assertTrue($type->readonly);

        $type->fill(['readonly'=> false]);

        $this->assertFalse($type->readonly);

        foreach (['protected', 'forbidden'] as $name) {
            $type->fill([$name=> false]);
            $this->assertFalse($type->readonly);
            $type->fill([$name=> true]);
            $this->assertTrue($type->readonly);
        }
    }

    public function test_isComplex_returns_expected()
    {
        $type = $this->newType();

        $awaited = in_array($type->group(), [XType::CUSTOM, XType::COMPLEX]);

        $this->assertEquals($awaited, $type->isComplex());
    }

    public function test_isScalar_returns_expected()
    {
        $type = $this->newType();

        $awaited = in_array($type->group(), [XType::NUMBER, XType::STRING, XType::BOOL]);

        $this->assertEquals($awaited, $type->isScalar());
    }

    public function test_replicate_returns_identical_copy()
    {
        $type = $this->newType();
        $type->canBeNull = false;
        $type->defaultValue = 'X';
        $type->readonly = true;

        $copy = $type->replicate();
        $this->assertNotSame($type, $copy);
        $this->assertEquals($type->toArray(), $copy->toArray());
        $this->assertNotSame($type->getConstraints(), $copy->getConstraints());
        $this->assertEquals((string)$type->getConstraints(), (string)$copy->getConstraints());
    }

    public function test_toArray_without_constraints()
    {
        if (self::class != static::class) {
            return;
        }

        $type = $this->newType();
        $type->defaultValue = 'X';
        $type->readonly = true;

        $awaited = [
            'name' => 'abstract-type-test',
            'defaultValue' => 'X',
            'readonly' => true
        ];
        $this->assertEquals($awaited, $type->toArray());
    }

    public function test_get_throws_exception_if_key_not_found()
    {
        $this->expectException(NotFound::class);
        $this->newType()->foo;
    }

    public function test_isset_returns_right_value_on_existing_properties()
    {
        $type = $this->newType();
        $this->assertFalse(isset($type->foo));
        $this->assertTrue(isset($type->readonly));
    }

    public function test_isset_returns_right_value_on_existing_getters()
    {
        $type = $this->newType();
        $this->assertFalse(isset($type->meNotAsAGetter));
        $this->assertTrue(isset($type->name));
        $this->assertTrue(isset($type->constraints));
    }

    public function test_isset_returns_right_value_on_setted_constraints()
    {
        $type = $this->newType();
        $this->assertFalse(isset($type->min));
        $type->constraints->min = 3;
        $this->assertTrue(isset($type->min));
        $this->assertTrue(isset($type->constraints->min));
    }

    public function test_unset_throws_exception_if_trying_to_delete_a_getter_managed_property()
    {
        $this->expectException(BadMethodCallException::class);
        $type = $this->newType();
        unset($type->name);
    }

    public function test_unset_removes_constraint()
    {
        $type = $this->newType();
        $this->assertFalse(isset($type->min));
        $type->min = 15;
        $this->assertTrue(isset($type->min));
        $this->assertTrue(isset($type->constraints->min));
        unset($type->min);
        $this->assertFalse(isset($type->constraints->min));
        $this->assertFalse(isset($type->min));

    }

    protected function newType()
    {
        return new AbstractTypeTestType();
    }
}

class AbstractTypeTestType extends AbstractType
{
    public function group()
    {
        return self::NONE;
    }

    public static function getMeNotAsAGetter()
    {

    }

    public static function setMeNotAsASetter($value)
    {

    }
}

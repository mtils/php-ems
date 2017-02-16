<?php

namespace Ems\XType;

use Ems\Contracts\XType\XType;

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

    public function test_fill_fills_canBeNull_with_all_aliases()
    {
        $type = $this->newType();

        $type->fill(['canBeNull'=> false]);

        $this->assertFalse($type->canBeNull);

        $type->fill(['canBeNull'=> true]);

        $this->assertTrue($type->canBeNull);

        $type->fill(['required'=> true]);

        $this->assertFalse($type->canBeNull);

        $type->fill(['required'=> false]);

        $this->assertTrue($type->canBeNull);

        $type->fill(['null'=> false]);

        $this->assertFalse($type->canBeNull);

        $type->fill(['null'=> true]);

        $this->assertTrue($type->canBeNull);

        $type->fill(['optional'=> false]);

        $this->assertFalse($type->canBeNull);
    }

    public function test_fill_fills_mustBeTouched_with_all_aliases()
    {
        $type = $this->newType();

        $type->fill(['mustBeTouched'=> true]);

        $this->assertTrue($type->mustBeTouched);

        $type->fill(['mustBeTouched'=> false]);

        $this->assertFalse($type->mustBeTouched);

        $type->fill(['touched'=> true]);

        $this->assertTrue($type->mustBeTouched);

        $type->fill(['touched'=> false]);

        $this->assertFalse($type->mustBeTouched);

        foreach (['ignore', 'ignored'] as $name) {
            $type->fill([$name=> false]);
            $this->assertTrue($type->mustBeTouched);
            $type->fill([$name=> true]);
            $this->assertFalse($type->mustBeTouched);
        }
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

    /**
     * @expectedException Ems\Core\Exceptions\UnsupportedParameterException
     **/
    public function test_fill_throws_exception_if_parameter_not_supported()
    {
        $type = $this->newType();

        $type->fill(['foo_crap_bar'=> true]);
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
        $type->mustBeTouched = true;
        $type->readonly = true;

        $copy = $type->replicate();
        $this->assertNotSame($type, $copy);
        $this->assertEquals($type->toArray(), $copy->toArray());
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
}

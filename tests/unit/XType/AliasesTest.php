<?php

namespace Ems\XType;

use Ems\Contracts\XType\XType;
use Ems\Contracts\XType\TypeFactory as TypeFactoryContract;
use Ems\Testing\Cheat;

class AliasesTest extends \Ems\TestCase
{

    public function test_url_returns_right_type()
    {
        $type = $this->xType('url');
        $this->assertInstanceOf(StringType::class, $type);
        $this->assertEquals(1, $type->min);
        $this->assertEquals(255, $type->max);
    }

    public function test_foreign_key_returns_correct_type()
    {
        $type = $this->xType('foreign_key');
        $this->assertInstanceOf(NumberType::class, $type);
        $this->assertEquals('int', $type->nativeType);
        $this->assertEquals(0, $type->decimalPlaces);
        $this->assertEquals(0, $type->precision);
        $this->assertTrue($type->readonly);
    }

    public function test_weight_returns_correct_type()
    {
        $type = $this->xType('weight');
        $this->assertInstanceOf(UnitType::class, $type);
        $this->assertEquals('float', $type->nativeType);
        $this->assertEquals(1, $type->decimalPlaces);
        $this->assertEquals(2, $type->precision);
        $this->assertFalse($type->readonly);
        $this->assertEquals('kg', $type->unit);
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\NotFound
     **/
    public function test_non_existing_type_throws_exception()
    {
        $aliases = $this->newAliases();
        $factory = new TypeFactory;
        $type = $aliases->toType('unpaid-bills', $factory);
    }

    public function test_second_call_uses_alias_cache()
    {
        $factory = $this->newFactory();
        $type = $factory->toType('weight');
        $type = $factory->toType('time');
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\ConfigurationError
     **/
    public function test_double_named_type_throws_exception()
    {
        $aliases = $this->newAliases();

        $proxy = Cheat::a($aliases);

        $aliasesArray = $proxy->aliases;

        $aliasesArray['number']['url'] = 'min:1|max:255';

        $proxy->aliases = $aliasesArray;

        $factory = $this->newFactory($aliases);

        $factory->toType('uri');
    }

    protected function xType($name)
    {
        return $this->newFactory()->toType($name);
    }

    protected function newFactory(Aliases $aliases=null)
    {
        $aliases = $aliases ?: $this->newAliases();
        $factory = new TypeFactory;

        $aliases->addTo($factory);

        return $factory;
    }

    protected function newAliases()
    {
        return new Aliases;
    }

}

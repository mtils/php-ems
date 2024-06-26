<?php

namespace Ems\XType;


use Ems\Contracts\Core\Errors\UnSupported;
use Ems\XType\AbstractTypeTest;
use Ems\Testing\LoggingCallable;

require_once __DIR__.'/AbstractTypeTest.php';


class ArrayAccessTypeTest extends AbstractTypeTest
{
    public function test_names_returns_added_names()
    {
        $type = $this->newType();

        $type->set('old', new BoolType());
        $type->set('selled', new BoolType());

        $this->assertEquals(['old', 'selled'], $type->names());
    }

    public function test_get_returns_added_type()
    {
        $type = $this->newType();

        $sub = new BoolType();
        $type['old'] = $sub;

        $this->assertSame($sub, $type->get('old'));
    }

    public function test_unset_removes_added_type()
    {
        $type = $this->newType();

        $sub = new BoolType();
        $type['old'] = $sub;

        $this->assertSame($sub, $type->get('old'));

        $this->assertCount(1, $type);

        unset($type['old']);

        $this->assertFalse($type->offsetExists('old'));

        $this->assertCount(0, $type);
    }

    public function test_iteration_over_type_returns_names_and_types()
    {
        $type = $this->newType();

        $old = new BoolType();
        $selled = new BoolType();

        $type->set('old', $old);
        $type->set('selled', $selled);

        $awaited = [
            'old'    => $old,
            'selled' => $selled
        ];

        $output = [];

        foreach ($type as $name=>$value) {
            $output[$name] = $value;
        }

        $this->assertEquals($awaited, $output);
    }

    public function test_get_throws_OutOfBoundsException_if_name_not_assigned()
    {
        $this->expectException(\OutOfBoundsException::class);
        $type = $this->newType();
        $type->get('foo');
    }

    public function test_fill_with_unknown_keys_will_add_as_named_types()
    {
        $type = $this->newType();

        $old = new BoolType();
        $selled = new BoolType();

        $fill = [
            'old'       => $old,
            'selled'    => $selled,
            'required'  => true
        ];

        $type->fill($fill);

        $this->assertTrue($type->notNull);
        $this->assertSame($old, $type['old']);
        $this->assertSame($selled, $type['selled']);
    }

    public function test_fill_with_unknown_not_xtype_values_throws_exception()
    {
        $this->expectException(Unsupported::class);
        $type = $this->newType();

        $old = new BoolType();
        $selled = new BoolType();

        $fill = [
            'old'       => $old,
            'selled'    => $selled,
            'required'  => true,
            'test' => new \stdClass
        ];

        $type->fill($fill);

        $this->assertTrue($type->notNull);
        $this->assertSame($old, $type['old']);
        $this->assertSame($selled, $type['selled']);
    }

    public function test_provideKeysBy_assigns_keyProvider_and_calls_it()
    {
        $type = $this->newType();

        $provider = new LoggingCallable(function() {
            return [
                'id' => new NumberType,
                'name' => new StringType
            ];
        });

        $this->assertSame($type, $type->provideKeysBy($provider));

        $this->assertInstanceOf(NumberType::class, $type->get('id'));
        $this->assertInstanceOf(StringType::class, $type->get('name'));
    }

    protected function newType()
    {
        return new ArrayAccessType();
    }
}

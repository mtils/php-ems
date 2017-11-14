<?php

namespace Ems\Core;

use Ems\Contracts\Core\ArrayWithState as ArrayWithStateContract;
use Ems\Core\Support\PublicAttributeFilling;
use Ems\Testing\LoggingCallable;

require_once(__DIR__.'/ArrayWithStateTest.php');

class QueryableArrayWithStateTest extends ArrayWithStateTest
{

    public function test_nested_key_exists()
    {
        $array = $this->newArray();
        $array['foo'] = [
            'bar' => 'hello'
        ];

        $this->assertTrue(isset($array['foo.bar']));
    }

    public function test_nested_key_does_not_exist()
    {
        $array = $this->newArray();
        $array['foo'] = [
            'bar' => 'hello'
        ];

        $this->assertFalse(isset($array['foo.fii']));
        $this->assertFalse(isset($array['fii']));
    }

    public function test_get_nested_key()
    {
        $array = $this->newArray();
        $array['foo'] = [
            'bar' => 'hello'
        ];

        $this->assertEquals('hello', $array['foo.bar']);
    }

    public function test_get_unexisting_nested_key_returns_null()
    {
        $array = $this->newArray();
        $array['foo'] = [
            'bar' => 'hello'
        ];

        $this->assertNull($array['fii']);
        $this->assertNull($array['foo.blob']);
    }

    public function test_set_nested_key()
    {
        $awaited = [
            'foo' => [
                'bar' => 'hello'
            ]
        ];

        $array = $this->newArray();
        $array['foo.bar'] = 'hello';

        $this->assertEquals('hello', $array['foo.bar']);
        $this->assertEquals($awaited, $array->toArray());
    }

    public function test_set_nested_key_does_not_destroy_other_values()
    {
        $awaited = [
            'foo' => [
                'bar' => 'hello',
                'blub'=> 'test'
            ]
        ];

        $array = $this->newArray();
        $array['foo.bar'] = 'hello';
        $array['foo.blub'] = 'test';

        $this->assertEquals('hello', $array['foo.bar']);
        $this->assertEquals('test', $array['foo.blub']);
        $this->assertEquals($awaited, $array->toArray());
    }

    public function test_unset_nested_key_removes_key()
    {
        $awaited = [
            'foo' => [
                'blub'=> 'test'
            ]
        ];

        $array = $this->newArray();
        $array['foo.bar'] = 'hello';
        $array['foo.blub'] = 'test';

        $this->assertEquals('hello', $array['foo.bar']);
        $this->assertEquals('test', $array['foo.blub']);

        unset($array['foo.bar']);

        $this->assertEquals($awaited, $array->toArray());
        $this->assertNull($array['foo.bar']);
        $this->assertEquals('test', $array['foo.blub']);
        
    }

    public function newArray()
    {
        return new QueryableArrayWithState;
    }

}

<?php


namespace Ems\Core\Support;

use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ObjectReadAccessTest extends TestCase
{

    #[Test] public function isset_returns_right_value()
    {
        $object = $this->newObject();
        $this->assertTrue(isset($object->number));
        $this->assertTrue(isset($object->foo));
        $this->assertFalse(isset($object->baby));

    }

    #[Test] public function isset_returns_true_on_null()
    {
        $object = $this->newObject();
        $this->assertTrue(isset($object->nothing));
    }

    #[Test] public function get_returns_setted_value()
    {
        $object = $this->newObject();
        $this->assertEquals(15, $object->number);
        $this->assertEquals('bar', $object->foo);
        $this->assertNull($object->nothing);
    }

    #[Test] public function get_throws_exception_if_key_not_found()
    {
        $this->expectException(
            \Ems\Core\Exceptions\KeyNotFoundException::class
        );
        $this->newObject()->bar;
    }

    #[Test] public function get_throws_exception_if_properties_not_implemented()
    {
        $this->expectException(
            \Ems\Core\Exceptions\NotImplementedException::class
        );
        $object = new ObjectReadAccessTest_Object_without_Properties();
        $object->bar;
    }

    #[Test] public function get_throws_no_exception_if_it_should_not_fail_on_missing_properties()
    {
        $object = new ObjectReadAccessTest_Object_that_allows_missing_properties();
        $object->bar;
    }

    protected function newObject()
    {
        return new ObjectReadAccessTest_Object();
    }
}

class ObjectReadAccessTest_Object {
    use ObjectReadAccess;

    protected $_properties = [
        'nothing' => null,
        'number'  => 15,
        'foo'     => 'bar'
    ];
}

class ObjectReadAccessTest_Object_without_Properties
{
    use ObjectReadAccess;
}

class ObjectReadAccessTest_Object_that_allows_missing_properties extends ObjectReadAccessTest_Object {
    /**
     * @return bool
     */
    protected function shouldFailOnMissingProperty()
    {
        return false;
    }

}
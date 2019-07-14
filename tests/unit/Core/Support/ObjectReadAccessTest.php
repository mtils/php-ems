<?php


namespace Ems\Core\Support;

use Ems\TestCase;

class ObjectReadAccessTest extends TestCase
{

    /**
     * @test
     */
    public function isset_returns_right_value()
    {
        $object = $this->newObject();
        $this->assertTrue(isset($object->number));
        $this->assertTrue(isset($object->foo));
        $this->assertFalse(isset($object->baby));

    }

    /**
     * @test
     */
    public function isset_returns_true_on_null()
    {
        $object = $this->newObject();
        $this->assertTrue(isset($object->nothing));
    }

    /**
     * @test
     */
    public function get_returns_setted_value()
    {
        $object = $this->newObject();
        $this->assertEquals(15, $object->number);
        $this->assertEquals('bar', $object->foo);
        $this->assertNull($object->nothing);
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\KeyNotFoundException
     */
    public function get_throws_exception_if_key_not_found()
    {
        $this->newObject()->bar;
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function get_throws_exception_if_properties_not_implemented()
    {
        $object = new ObjectReadAccessTest_Object_without_Properties();
        $object->bar;
    }

    /**
     * @test
     */
    public function get_throws_no_exception_if_it_should_not_fail_on_missing_properties()
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
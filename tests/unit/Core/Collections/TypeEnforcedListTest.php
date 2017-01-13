<?php 

namespace Ems\Core\Collections;

use Ems\Testing\LoggingCallable;

require_once __DIR__.'/OrderedListTest.php';

class TypeEnforcedListTest extends OrderedListTest
{
    public function test_implements_interface()
    {
        $this->assertInstanceof('ArrayAccess', $this->newList());
        $this->assertInstanceof('IteratorAggregate', $this->newList());
        $this->assertInstanceof('Countable', $this->newList());
    }

    public function test_getForcedType_returns_setted_type()
    {
        $list = $this->newList()->setForcedType('int');
        $this->assertEquals('int', $list->getForcedType());
    }

    /**
     * @expectedException BadMethodCallException
     **/
    public function test_setForcedType_throws_exception_if_type_is_frozen()
    {
        $list = $this->newList()->setForcedType('int');
        $list->freezeType();
        $list->setForcedType('string');
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_append_non_int_items_throws_InvalidArgumentException()
    {
        $list = $this->newList()->setForcedType('int');
        $list->append('string');
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_append_non_bool_items_throws_InvalidArgumentException()
    {
        $list = $this->newList()->setForcedType('bool');
        $list->append(1);
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_append_non_float_items_throws_InvalidArgumentException()
    {
        $list = $this->newList()->setForcedType('float');
        $list->append(1);
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_append_non_resource_items_throws_InvalidArgumentException()
    {
        $list = $this->newList()->setForcedType('resource');
        $list->append(1);
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_append_non_array_items_throws_InvalidArgumentException()
    {
        $list = $this->newList()->setForcedType('array');
        $list->append(1);
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_append_non_object_items_throws_InvalidArgumentException()
    {
        $list = $this->newList()->setForcedType('object');
        $list->append(1);
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_append_non_class_items_throws_InvalidArgumentException()
    {
        $list = $this->newList()->setForcedType(_ForcedClass::class);
        $list->append(new \stdClass());
    }

    protected function newList($params=null)
    {
        $list = new TypeEnforcedList();
        $list->setForcedType('');

        if ($params) {
            $list->setSource($params);
        }
        return $list;
    }
}

class _ForcedClass
{
}

<?php

namespace Ems\Core;


use Ems\Contracts\Core\Errors\NotFound;
use Ems\Contracts\Core\Provider;
use Ems\Contracts\Core\AllProvider;
use Ems\Testing\LoggingCallable;
use InvalidArgumentException;

class AnythingProviderTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(Provider::class, $this->newProvider());
        $this->assertInstanceOf(AllProvider::class, $this->newProvider());
    }

    public function test_get_returns_default_if_item_not_found()
    {
        $this->assertEquals('foo', $this->newProvider()->get('bar', 'foo'));
    }

    public function test_getOrFail_throws_exception_if_item_not_found()
    {
        $this->expectException(NotFound::class);
        $this->assertEquals('foo', $this->newProvider()->getOrFail('bar'));
    }

    public function test_get_and_getOrFail_returns_item()
    {
        $provider = $this->newProvider();
        $item = (object)['id'=>'foo', 'name'=>'henry'] ;
        $provider->set('foo', $item);
        $this->assertSame($item, $provider->get('foo'));
        $this->assertSame($item, $provider->getOrFail('foo'));
    }

    public function test_all_returns_all_added_items()
    {
        $provider = $this->newProvider();

        $items = [
            (object)['id'=>'foo', 'name'=>'jill'],
            (object)['id'=>'bar', 'name'=>'henry'],
            (object)['id'=>'off', 'name'=>'jane']
        ];

        foreach ($items as $item) {
            $provider->set($item->id, $item);
        }

        $this->assertEquals($items, $provider->all());
    }

    public function test_set_throws_exception_if_different_type_enforced()
    {
        $this->expectException(InvalidArgumentException::class);
        $provider = new _AnytingTestProviderTest();
        $item = (object)['id'=>'foo', 'name'=>'henry'] ;
        $provider->set('foo', $item);
    }

    public function test_removal_of_item_removes_it()
    {
        $provider = $this->newProvider();

        $items = [
            (object)['id'=>'foo', 'name'=>'jill'],
            (object)['id'=>'bar', 'name'=>'henry'],
            (object)['id'=>'off', 'name'=>'jane']
        ];

        foreach ($items as $item) {
            $provider->set($item->id, $item);
        }

        $this->assertSame($items[1], $provider->get('bar'));

        $provider->remove('bar');

        $this->assertNull($provider->get('bar'));
    }

    public function test_getClass_returns_class_setted_via_setClass()
    {
        $provider = $this->newProvider();

        $provider->setClass('foo', \stdClass::class);
        $this->assertNull($provider->getClass('bar'));
        $this->assertEquals(\stdClass::class, $provider->getClass('foo'));
    }

    public function test_get_creates_object_with_class_setted_via_setClass()
    {
        $provider = $this->newProvider();

        $provider->setClass('foo', \stdClass::class);
        $object = $provider->get('foo');
        $this->assertInstanceOf(\stdClass::class, $object);
        $this->assertSame($object, $provider->get('foo'));
        $this->assertSame($object, $provider->getOrFail('foo'));
    }

    public function test_setClass_of_created_item_removes_item()
    {
        $provider = $this->newProvider();

        $provider->setClass('foo', \stdClass::class);
        $object = $provider->get('foo');
        $this->assertInstanceOf(\stdClass::class, $object);
        $this->assertSame($object, $provider->get('foo'));
        $provider->setClass('foo', _AnytingTestProviderTestItem::class);
        $this->assertInstanceOf(_AnytingTestProviderTestItem::class, $provider->get('foo'));
    }

    public function test_removeClass_of_created_item_removes_also_item()
    {
        $provider = $this->newProvider();

        $provider->setClass('foo', \stdClass::class);
        $object = $provider->get('foo');
        $this->assertInstanceOf(\stdClass::class, $object);
        $this->assertSame($object, $provider->get('foo'));
        $provider->removeClass('foo');
        $this->assertNull($provider->get('foo'));
        $this->assertNull($provider->getClass('foo'));
    }

    public function test_all_creates_all_assigned_classes()
    {
        $provider = $this->newProvider();
        $items = [
            (object)['id'=>'foo', 'name'=>'jill'],
            (object)['id'=>'bar', 'name'=>'henry'],
            (object)['id'=>'off', 'name'=>'jane']
        ];

        foreach ($items as $item) {
            $provider->set($item->id, $item);
        }

        $items[] = new \stdClass();
        $items[] = new _AnytingTestProviderTestItem();


        $provider->setClass('more', \stdClass::class);
        $provider->setClass('even-more', _AnytingTestProviderTestItem::class);

        $this->assertEquals($items, $provider->all());
        $this->assertEquals($items, $provider->all());
    }

    public function test_get_creates_objects_with_assigned_creator()
    {
        $provider = $this->newProvider();
        $creator = new LoggingCallable(function ($class) { return new $class();});
        $provider->createObjectsWith($creator);

        $provider->setClass('foo', \stdClass::class);
        $object = $provider->get('foo');
        $this->assertInstanceOf(\stdClass::class, $object);
        $this->assertSame($object, $provider->get('foo'));
        $this->assertSame($object, $provider->getOrFail('foo'));
        $this->assertCount(1, $creator);
        $this->assertEquals(\stdClass::class, $creator->arg(0));
    }

    protected function newProvider()
    {
        return new AnythingProvider();
    }
}

class _AnytingTestProviderTest extends AnythingProvider
{
    protected $forceType = _AnytingTestProviderTestItem::class;
}

class _AnytingTestProviderTestItem
{
}

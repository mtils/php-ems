<?php

namespace Ems\Core;


use Ems\Contracts\Core\Provider;
use Ems\Contracts\Core\AllProvider;

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

    /**
     * @expectedException Ems\Contracts\Core\Errors\NotFound
     **/
    public function test_getOrFail_throws_exception_if_item_not_found()
    {
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

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_set_throws_exception_if_different_type_enforced()
    {
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

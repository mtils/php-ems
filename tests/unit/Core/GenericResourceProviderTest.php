<?php

namespace Ems\Core;


use Ems\Contracts\Core\Provider;
use Ems\Contracts\Core\AllProvider;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\Named;
use Ems\Contracts\Core\TextProvider;
use Ems\Core\NamedObject;

class GenericResourceProviderTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(Provider::class, $this->newProvider());
        $this->assertInstanceOf(AllProvider::class, $this->newProvider());
        $this->assertInstanceOf(AppliesToResource::class, $this->newProvider());
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

        $provider->add('my.object');
        $this->assertInstanceOf(Named::class, $provider->get('my.object'));
        $this->assertInstanceOf(Named::class, $provider->getOrFail('my.object'));

        $item = new NamedObject('other.object', 'Manfred', 'objects');
        $provider->add($item);

        $this->assertSame($item, $provider->get('other.object'));
    }

    public function test_all_returns_all_added_items()
    {
        $provider = $this->newProvider();

        $provider->add('my.object');

        $item1 = $provider->get('my.object');
        $this->assertEquals('my.object', $item1->getId());
        $this->assertInstanceOf(Named::class, $item1);

        $item2 = new NamedObject('other.object', 'Manfred', 'objects');
        $provider->add($item2);

        $this->assertSame($item2, $provider->get('other.object'));

        $this->assertEquals([$item1, $item2], $provider->all());
    }

    public function test_resourceName_is_applied_to_items()
    {
        $provider = $this->newProvider();
        $provider->setResourceName('objects');

        $this->assertEquals($provider->resourceName(), 'objects');

        $provider->add('my.object');

        $this->assertEquals('objects', $provider->get('my.object')->resourceName());
    }

    public function test_name_is_taken_from_TextProvider()
    {
        $texts = $this->mock(TextProvider::class);
        $provider = $this->newProvider($texts);
        $texts->shouldReceive('get')
              ->with('my.object')
              ->atLeast()->once()
              ->andReturn('My nice object');

        $provider->add('my.object');

        $this->assertEquals('My nice object', $provider->get('my.object')->getName());
    }

    public function test_id_is_escaped_when_passed_to_TextProvider()
    {
        $texts = $this->mock(TextProvider::class);
        $provider = $this->newProvider($texts);
        $provider->replaceInKeys('.', '/');

        $texts->shouldReceive('get')
              ->with('my/object')
              ->atLeast()->once()
              ->andReturn('My nice object');

        $provider->add('my.object');

        $this->assertEquals('My nice object', $provider->get('my.object')->getName());
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_adding_not_named_object_throws_exception()
    {
        $provider = $this->newProvider();
        $provider->set('foo', new \stdClass());
    }

    protected function newProvider(TextProvider $texts=null)
    {
        return new GenericResourceProvider($texts);
    }
}

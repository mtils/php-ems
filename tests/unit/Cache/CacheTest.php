<?php

namespace Ems\Cache;

use Ems\Contracts\Cache\Cache as CacheContract;
use Ems\Contracts\Cache\Categorizer;
use Ems\Contracts\Cache\Storage;
use DateTime;
use Ems\Testing\LoggingCallable;
use Mockery as m;

class CacheTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(CacheContract::class, $this->newCache());
    }

    public function test_key_forwards_to_categorizer()
    {
        $categorizer = $this->mockCategorizer();
        $storage = $this->mockStorage();
        $cache = $this->newCache();
        $cache->addStorage('default', $storage);

        $storage->shouldReceive('escape')->with('foo')->andReturn('bar');

        $this->assertEquals('bar', $cache->key('foo'));
    }

    public function test_has_forwards_to_storage()
    {
        $storage = $this->mockStorage();
        $cache = $this->newCache();
        $cache->addStorage('default', $storage);

        $storage->shouldReceive('has')->with('foo')->andReturn(true);
        $storage->shouldReceive('has')->with('bar')->andReturn(false);

        $this->assertTrue($cache->has('foo'));
        $this->assertFalse($cache->has('bar'));
        $this->assertTrue(isset($cache['foo']));
        $this->assertFalse(isset($cache['bar']));
    }

    public function test_get_does_not_ask_categorizer_if_key_is_string()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $categorizer->shouldReceive('key')->never();

        $storage->shouldReceive('has')->with('foo')->andReturn(true);
        $storage->shouldReceive('get')->with('foo')->andReturn('bar');

        $this->assertEquals('bar', $cache->get('foo'));
        $this->assertEquals('bar', $cache['foo']);
    }

    public function test_get_asks_categorizer_if_key_is_cacheable_array()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $key = ['a'=> 'b'];

        $categorizer->shouldReceive('key')->with($key)->andReturn('a_array');
        $storage->shouldReceive('escape')->with('a_array')->andReturn('a_array');

        $storage->shouldReceive('has')->with('a_array')->andReturn(true);
        $storage->shouldReceive('get')->with('a_array')->andReturn('bar');

        $this->assertEquals('bar', $cache->get($key));
    }

    public function test_get_returns_null_if_default_is_null_and_no_hit()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $storage->shouldReceive('get')->with('a')->andReturn(null);

        $this->assertNull($cache->get('a'));
    }

    public function test_get_passes_array_of_strings_to_storage()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $keys = ['a','b','c'];

        $storage->shouldReceive('get')->with($keys)->andReturn(true);

        $this->assertTrue($cache->get($keys));
    }

    public function test_get_stores_value_and_returns_it_if_default_value_passed()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $key = 'foo';
        $passed = 'bar';
        $tags = ['a','b'];
        $lifetime = new DateTime();

        $categorizer->shouldReceive('tags')->andReturn($tags);
        $categorizer->shouldReceive('lifetime')->andReturn($lifetime);

        $storage->shouldReceive('has')->with($key)->andReturn(false);
        $storage->shouldReceive('put')
                ->with($key, $passed, $tags, $lifetime)
                ->andReturn($storage);

        $this->assertEquals($passed, $cache->get($key, $passed));
    }

    public function test_get_stores_value_and_returns_it_if_callable_default_passed()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $key = 'foo';
        $result = 'cache_result';
        $callable = new LoggingCallable(function () use ($result) { return $result; });
        $tags = ['a','b'];
        $lifetime = new DateTime();

        $categorizer->shouldReceive('tags')->andReturn($tags);
        $categorizer->shouldReceive('lifetime')->andReturn($lifetime);

        $storage->shouldReceive('has')->with($key)->andReturn(false);
        $storage->shouldReceive('put')
                ->with($key, $result, $tags, $lifetime)
                ->andReturn($storage);

        $this->assertEquals($result, $cache->get($key, $callable));
    }

    public function test_getOrFail_returns_value_on_cache_hit()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $storage->shouldReceive('has')->with('a')->andReturn(true);
        $storage->shouldReceive('get')->with('a')->andReturn('bar');

        $this->assertEquals('bar', $cache->getOrFail('a'));
    }

    /**
     * @expectedException Ems\Cache\Exception\CacheMissException
     **/
    public function test_getOrFail_throws_exception_if_value_not_found()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $storage->shouldReceive('has')->with('a')->andReturn(false);

        $cache->getOrFail('a');
    }

    public function test_until_passes_lifetime_to_storage()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $key = 'foo';
        $passed = 'bar';
        $tags = ['a','b'];
        $lifetime = '3 days';
        $until = (new DateTime())->modify("+$lifetime");
        $wrongLifeTime = new DateTime('2014-08-01 00:00:00');

        $categorizer->shouldReceive('tags')->andReturn($tags);
        $categorizer->shouldReceive('lifetime')->andReturn($wrongLifeTime);

        $storage->shouldReceive('has')->with($key)->andReturn(false);
        $storage->shouldReceive('put')
                ->with($key, $passed, $tags, $until)
                ->andReturn($storage);

        $this->assertInstanceOf(CacheContract::class, $cache->until($until)->put($key, $passed));
    }

    public function test_offsetSet_passes_put_to_storage()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $key = 'foo';
        $passed = 'bar';
        $tags = ['a','b'];
        $lifetime = '3 days';
        $until = (new DateTime())->modify("+$lifetime");
        $wrongLifeTime = new DateTime('2014-08-01 00:00:00');

        $categorizer->shouldReceive('tags')->andReturn($tags);
        $categorizer->shouldReceive('lifetime')->andReturn($until);

        $storage->shouldReceive('has')->with($key)->andReturn(false);
        $storage->shouldReceive('put')
                ->with($key, $passed, $tags, $until)
                ->andReturn($storage);

        $cache[$key] = $passed;
    }

    public function test_until_passes_tags_to_storage()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $key = 'foo';
        $passed = 'bar';
        $tags = ['a','b'];
        $wrongTags = [];
        $lifetime = '3 days';
        $until = (new DateTime())->modify("+$lifetime");

        $categorizer->shouldReceive('tags')->andReturn($wrongTags);
        $categorizer->shouldReceive('lifetime')->andReturn($until);

        $storage->shouldReceive('has')->with($key)->andReturn(false);
        $storage->shouldReceive('put')
                ->with($key, $passed, $tags, $until)
                ->andReturn($storage);

        $this->assertInstanceOf(CacheContract::class, $cache->tag($tags)->put($key, $passed));
    }

    public function test_storage_passes_add_to_different_storage()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $storage2 = $this->mockStorage();
        $cache->addStorage('default', $storage);
        $cache->addStorage('storage1', $storage2);

        $key = 'foo';
        $passed = 'bar';
        $tags = ['a','b'];
        $wrongTags = [];
        $lifetime = '3 days';
        $until = (new DateTime())->modify("+$lifetime");

        $categorizer->shouldReceive('tags')->andReturn($tags);
        $categorizer->shouldReceive('lifetime')->andReturn($until);

        $storage2->shouldReceive('has')->with($key)->andReturn(false);
        $storage2->shouldReceive('put')
                 ->with($key, $passed, $tags, $until)
                 ->andReturn($storage);

        $this->assertInstanceOf(CacheContract::class, $cache->storage('storage1')->put($key, $passed));
    }

    public function test_increment_forwards_to_storage()
    {
        $storage = $this->mockStorage();
        $cache = $this->newCache();
        $cache->addStorage('default', $storage);

        $storage->shouldReceive('increment')->with('foo', 2)->atLeast()->once();

        $this->assertSame($cache, $cache->increment('foo', 2));
    }

    public function test_decrement_forwards_to_storage()
    {
        $storage = $this->mockStorage();
        $cache = $this->newCache();
        $cache->addStorage('default', $storage);

        $storage->shouldReceive('decrement')->with('foo', 2)->atLeast()->once();

        $this->assertSame($cache, $cache->decrement('foo', 2));
    }

    public function test_persist_does_nothing()
    {
        $storage = $this->mockStorage();
        $cache = $this->newCache();
        $cache->addStorage('default', $storage);

        $this->assertTrue($cache->persist());
    }

    public function test_purge_forwards_to_storage()
    {
        $storage = $this->mockStorage();
        $cache = $this->newCache();
        $cache->addStorage('default', $storage);

        $storage->shouldReceive('clear')->atLeast()->once()->andReturn(true);

        $this->assertTrue($cache->purge());
    }

    public function test_forget_forwards_to_storage()
    {
        $storage = $this->mockStorage();
        $cache = $this->newCache();
        $cache->addStorage('default', $storage);

        $storage->shouldReceive('forget')->with('foo')->twice()->andReturn($storage);

        $this->assertSame($cache, $cache->forget('foo'));
        unset($cache['foo']);
    }

    public function test_prune_forwards_to_storage()
    {
        $storage = $this->mockStorage();
        $cache = $this->newCache();
        $cache->addStorage('default', $storage);
        $tags = ['a','b'];

        $storage->shouldReceive('prune')->with($tags)->atLeast()->once()->andReturn(true);

        $this->assertSame($cache, $cache->prune($tags));
    }

    public function test_until_and_tags_forwards_to_storage()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $cache->addStorage('default', $storage);

        $key = 'foo';
        $passed = 'bar';
        $tags = ['a','b'];
        $wrongTags = [];
        $lifetime = '3 days';
        $until = (new DateTime())->modify("+$lifetime");
        $wrongLifeTime = new DateTime('2014-08-01 00:00:00');

        $categorizer->shouldReceive('tags')->andReturn($wrongTags);
        $categorizer->shouldReceive('lifetime')->andReturn($wrongLifeTime);

        $storage->shouldReceive('has')->with($key)->andReturn(false);
        $storage->shouldReceive('put')
                ->with($key, $passed, $tags, $until)
                ->andReturn($storage);

        $this->assertInstanceOf(CacheContract::class, $cache->tag($tags)->until($until)->put($key, $passed));
    }

    public function test_storage_until_and_tags_forwards_to_storage()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $storage2 = $this->mockStorage();
        $cache->addStorage('default', $storage);
        $cache->addStorage('fast', $storage2);

        $key = 'foo';
        $passed = 'bar';
        $tags = ['a','b'];
        $wrongTags = [];
        $lifetime = '3 days';
        $until = (new DateTime())->modify("+$lifetime");
        $wrongLifeTime = new DateTime('2014-08-01 00:00:00');

        $categorizer->shouldReceive('tags')->andReturn($wrongTags);
        $categorizer->shouldReceive('lifetime')->andReturn($wrongLifeTime);

        $storage2->shouldReceive('has')->with($key)->andReturn(false);
        $storage2->shouldReceive('put')
                 ->with($key, $passed, $tags, m::type(get_class($until)))
                 ->andReturn($storage2);

        $this->assertInstanceOf(CacheContract::class, $cache->storage('fast')->tag($tags)->until($lifetime)->put($key, $passed));
    }

    public function test_addStorage_on_proxy_until_and_tags_forwards_to_storage()
    {
        $categorizer = $this->mockCategorizer();
        $cache = $this->newCache($categorizer);
        $storage = $this->mockStorage();
        $storage2 = $this->mockStorage();
        $storage3 = $this->mockStorage();
        $cache->addStorage('default', $storage);
        $cache->addStorage('fast', $storage2);
        $cache->storage('fast')->addStorage('big', $storage3);

        $key = 'foo';
        $passed = 'bar';
        $tags = ['a','b'];
        $wrongTags = [];
        $lifetime = '3 days';
        $until = (new DateTime())->modify("+$lifetime");
        $wrongLifeTime = new DateTime('2014-08-01 00:00:00');

        $categorizer->shouldReceive('tags')->andReturn($wrongTags);
        $categorizer->shouldReceive('lifetime')->andReturn($wrongLifeTime);

        $storage3->shouldReceive('has')->with($key)->andReturn(false);
        $storage3->shouldReceive('put')
                 ->with($key, $passed, $tags, $until)
                 ->andReturn($storage3);

        $this->assertInstanceOf(CacheContract::class, $cache->storage('fast')->storage('big')->tag($tags)->until($until)->put($key, $passed));
    }

    public function test_getStorage_returns_assigned_storage()
    {
        $cache = $this->newCache();
        $storage = $this->mockStorage();
        $storage2 = $this->mockStorage();
        $cache->addStorage('default', $storage);
        $cache->addStorage('fast', $storage2);

        $this->assertSame($storage, $cache->getStorage('default'));
        $this->assertSame($storage, $cache->getStorage());
        $this->assertSame($storage2, $cache->getStorage('fast'));
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\NotFound
     **/
    public function test_getStorage_throws_NotFound_if_storage_not_assigned()
    {
        $cache = $this->newCache();
        $storage = $this->mockStorage();

        $cache->addStorage('default', $storage);
        $cache->getStorage('foo');
    }

    public function test_methodHooks_returns_hooks_for_all_altering_methods()
    {
        $cache = $this->newCache();
        $alteringMethods = ['put', 'increment', 'decrement', 'forget', 'prune', 'persist'];

        $hooks = $cache->methodHooks();

        foreach ($alteringMethods as $method) {
            $this->assertTrue(in_array($method, $hooks));
        }
    }

    protected function newCache(Categorizer $categorizer=null)
    {
        $categorizer = $categorizer ?: $this->mockCategorizer();
        return new Cache($categorizer);
    }

    protected function mockCategorizer()
    {
        return $this->mock(Categorizer::class);
    }

    protected function mockStorage()
    {
        return $this->mock(Storage::class);
    }
}

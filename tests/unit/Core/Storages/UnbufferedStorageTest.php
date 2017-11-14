<?php

namespace Ems\Core\Storages;



use Ems\Contracts\Core\BufferedStorage;
use Ems\Contracts\Core\UnbufferedStorage;
use Ems\Core\Collections\StringList;

class UnbufferedStorageTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(UnBufferedStorage::class, $this->newProxy());
    }

    public function test_forwards_to_offsetExists()
    {
        $storage = $this->mockStorage();
        $proxy = $this->newProxy($storage);

        $storage->shouldReceive('offsetExists')
                ->with('foo')
                ->once()
                ->andReturn(true);

        $this->assertTrue(isset($proxy['foo']));
    }

    public function test_forwards_to_offsetGet()
    {
        $storage = $this->mockStorage();
        $proxy = $this->newProxy($storage);

        $storage->shouldReceive('offsetGet')
                ->with('foo')
                ->once()
                ->andReturn('bar');

        $this->assertEquals('bar', $proxy['foo']);
    }

    public function test_forwards_to_offsetSet_and_persist()
    {
        $storage = $this->mockStorage();
        $proxy = $this->newProxy($storage);

        $storage->shouldReceive('offsetSet')
                ->with('foo', 'bar')
                ->once();

        $storage->shouldReceive('persist')
                ->andReturn(true)
                ->once();
        $this->assertNull($proxy->offsetSet('foo', 'bar'));
    }

    public function test_offsetUnset_forwards_to_purge()
    {
        $storage = $this->mockStorage();
        $proxy = $this->newProxy($storage);

        $storage->shouldReceive('purge')
                ->with(['foo'])
                ->once();

        $this->assertNull($proxy->offsetUnset('foo'));
    }

    public function test_clear_forwards_to_purge()
    {
        $storage = $this->mockStorage();
        $proxy = $this->newProxy($storage);

        $storage->shouldReceive('purge')
                ->with(null)
                ->once();

        $this->assertSame($proxy, $proxy->clear());
    }

    public function test_forwards_keys()
    {
        $storage = $this->mockStorage();
        $proxy = $this->newProxy($storage);
        $keys = new StringList(['foo']);

        $storage->shouldReceive('keys')
                ->once()
                ->andReturn($keys);

        $this->assertSame($keys, $proxy->keys());
    }

    public function test_forwards_toArray()
    {
        $storage = $this->mockStorage();
        $proxy = $this->newProxy($storage);
        $array = [ 'foo' => 'bar' ];

        $storage->shouldReceive('toArray')
                ->once()
                ->andReturn($array);

        $this->assertEquals($array, $proxy->toArray());
    }

    public function test_storageType_returns_utility()
    {
        $this->assertEquals(BufferedStorage::UTILITY, $this->newProxy()->storageType());
    }

    protected function newProxy(BufferedStorage $storage=null)
    {
        return new UnbufferedStorageProxy($storage ?: $this->mockStorage());
    }

    protected function mockStorage()
    {
        return $this->mock(BufferedStorage::class);
    }
}

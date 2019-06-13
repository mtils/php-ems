<?php
/**
 *  * Created by mtils on 12.09.18 at 12:31.
 **/

namespace Ems\Core\Storages;


use Ems\Contracts\Core\Storage;
use Ems\Core\Collections\StringList;
use Ems\TestCase;


class LittleDataCachedStorageTest extends TestCase
{


    /**
     * @test
     */
    public function implements_interface()
    {
        $this->assertInstanceOf(Storage::class, $this->newStorage());
    }

    /**
     * @test
     */
    public function storageType_is_memory()
    {
        $this->assertEquals(Storage::UTILITY, $this->newStorage()->storageType());
    }

    /**
     * @test
     */
    public function persist_returns_true()
    {
        $this->assertTrue($this->newStorage()->persist());
    }

    /**
     * @test
     */
    public function isBuffered_returns_true()
    {
        $this->assertFalse($this->newStorage()->isBuffered());
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\KeyNotFoundException
     */
    public function offsetGet_throws_exception_if_key_not_found()
    {
        $storage = $this->newStorage();
        $storage['foo'];
    }

    /**
     * @test
     */
    public function offsetSet_deletes_cache()
    {
        $data = ['test' => 'one','test2' => 'two'];
        $array = new ArrayStorage($data);
        $storage = $this->newStorage($array);
        $this->assertEquals($data['test'], $storage['test']);
        $storage['test'] = 'three';
        $this->assertEquals('three', $storage['test']);
    }

    /**
     * @test
     */
    public function offsetUnset_deletes_cache()
    {
        $data = ['test' => 'one', 'test2' => 'two'];
        $array = new ArrayStorage($data);
        $storage = $this->newStorage($array);
        $this->assertEquals($data['test'], $storage['test']);
        unset($storage['test']);
        $this->assertFalse(isset($storage['test']));
    }

    /**
     * @test
     */
    public function purge_clears_storage()
    {
        $data = ['test' => 'one'];
        $storage = $this->newStorage(new ArrayStorage($data));
        $this->assertEquals($data['test'],$storage['test']);
        $this->assertTrue($storage->purge());
        $this->assertFalse(isset($storage['test']));
    }

    /**
     * @test
     */
    public function clear_purges_storage()
    {
        $data = ['test' => 'one'];
        $storage = $this->newStorage(new ArrayStorage($data));
        $this->assertEquals($data['test'], $storage['test']);
        $this->assertSame($storage, $storage->clear());
        $this->assertFalse(isset($storage['test']));
    }

    /**
     * @test
     */
    public function keys()
    {
        $data = ['test' => 'one','test2' => 'two'];
        $array = new ArrayStorage($data);
        $storage = $this->newStorage($array);
        $keys = $storage->keys();
        $this->assertInstanceOf(StringList::class, $keys);
        $this->assertCount(2, $keys);
        $this->assertEquals('test', $keys[0]);
        $this->assertEquals('test2', $keys[1]);
    }

    /**
     * @param Storage $storage (optional)
     *
     * @return LittleDataCachedStorage
     */
    protected function newStorage(Storage $storage=null)
    {
        $storage = $storage ?: new ArrayStorage();
        return new LittleDataCachedStorage($storage);
    }
}
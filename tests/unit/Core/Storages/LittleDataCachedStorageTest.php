<?php
/**
 *  * Created by mtils on 12.09.18 at 12:31.
 **/

namespace Ems\Core\Storages;


use Ems\Contracts\Core\Storage;
use Ems\Core\Collections\StringList;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;


class LittleDataCachedStorageTest extends TestCase
{


    #[Test] public function implements_interface()
    {
        $this->assertInstanceOf(Storage::class, $this->newStorage());
    }

    #[Test] public function storageType_is_memory()
    {
        $this->assertEquals(Storage::UTILITY, $this->newStorage()->storageType());
    }

    #[Test] public function persist_returns_true()
    {
        $this->assertTrue($this->newStorage()->persist());
    }

    #[Test] public function isBuffered_returns_true()
    {
        $this->assertFalse($this->newStorage()->isBuffered());
    }

    #[Test] public function offsetGet_throws_exception_if_key_not_found()
    {
        $this->expectException(
            \Ems\Core\Exceptions\KeyNotFoundException::class
        );
        $storage = $this->newStorage();
        $storage['foo'];
    }

    #[Test] public function offsetSet_deletes_cache()
    {
        $data = ['test' => 'one','test2' => 'two'];
        $array = new ArrayStorage($data);
        $storage = $this->newStorage($array);
        $this->assertEquals($data['test'], $storage['test']);
        $storage['test'] = 'three';
        $this->assertEquals('three', $storage['test']);
    }

    #[Test] public function offsetUnset_deletes_cache()
    {
        $data = ['test' => 'one', 'test2' => 'two'];
        $array = new ArrayStorage($data);
        $storage = $this->newStorage($array);
        $this->assertEquals($data['test'], $storage['test']);
        unset($storage['test']);
        $this->assertFalse(isset($storage['test']));
    }

    #[Test] public function purge_clears_storage()
    {
        $data = ['test' => 'one'];
        $storage = $this->newStorage(new ArrayStorage($data));
        $this->assertEquals($data['test'],$storage['test']);
        $this->assertTrue($storage->purge());
        $this->assertFalse(isset($storage['test']));
    }

    #[Test] public function clear_purges_storage()
    {
        $data = ['test' => 'one'];
        $storage = $this->newStorage(new ArrayStorage($data));
        $this->assertEquals($data['test'], $storage['test']);
        $this->assertSame($storage, $storage->clear());
        $this->assertFalse(isset($storage['test']));
    }

    #[Test] public function keys()
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
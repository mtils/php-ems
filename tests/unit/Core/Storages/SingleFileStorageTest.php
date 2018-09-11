<?php

namespace Ems\Core\Storages;


use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Storage as StorageContract;
use Ems\Core\Serializer;
use Ems\Core\Url;
use Ems\Testing\FilesystemMethods;

class SingleFileStorageTest extends \Ems\TestCase
{
    use FilesystemMethods;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(StorageContract::class, $this->newStorage());
    }

    public function test_isBuffered_returns_true()
    {
        $this->assertTrue($this->newStorage()->isBuffered());
    }

    public function test_getUrl_returns_setted_url()
    {
        $storage = $this->newStorage();
        $url = new Url('/home/michael');
        $this->assertSame($storage, $storage->setUrl($url));
        $this->assertSame($url, $storage->getUrl());
    }

    public function test_storageType_returns_type()
    {
        $this->assertEquals('filesystem', $this->newStorage()->storageType());
    }

    public function test_persist_and_return_value()
    {
        $storage = $this->newStorage();
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        $storage->persist();
        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);
    }

    public function test_auto_persist_on_offsetSet_if_write_on_change_option_is_setted()
    {
        $storage = $this->newStorage();
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        $storage->persist();

        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);
    }

    public function test_auto_persist_on_offsetUnset_if_write_on_change_option_is_setted()
    {
        $storage = $this->newStorage();
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        $storage->persist();

        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);

        unset($storage2['a']);
        $storage2->persist();
        unset($storage2);


        $storage3 = $this->newStorage()->setUrl($url);
        $this->assertEquals('bar', $storage3['foo']);
        $this->assertFalse(isset($storage3['a']));
    }

    public function test_persist_and_return_value_without_checksum()
    {
        $storage = $this->newStorage(null, null, false);
        $storage->setOption('checksum_method', '');
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        $storage->persist();
        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\DataCorruption
     **/
    public function test_persist_throws_exception_if_checksum_failed()
    {
        $storage = $this->newStorage(null, null, false);

        $storage->createChecksumBy(function ($method, $data) {
            return substr(md5(microtime()), rand(0, 26), 5);
        });

        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        $storage->persist();
        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);
    }

    public function test_purge_empties_storage()
    {
        $storage = $this->newStorage(null, null, false);
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        $storage->persist();
        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);
        $storage2->purge();

        $this->assertFalse(isset($storage['bar']));
        $this->assertFalse(isset($storage['a']));
    }

    public function test_purge_with_passed_keys()
    {
        $storage = $this->newStorage(null, null, false);
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        $storage->persist();
        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);

        $this->assertFalse($storage2->purge([]));
        $this->assertTrue($storage2->purge(['bar']));

        $this->assertFalse(isset($storage2['bar']));
        $this->assertTrue(isset($storage2['a']));
    }

    public function test_isset_triggers_load()
    {
        $storage = $this->newStorage(null, null, false);
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $this->assertFalse(isset($storage['foo']));
    }

    protected function newStorage(Filesystem $files=null, SerializerContract $serializer=null, $autoPersist=true)
    {
        $files = $files ?: $this->newFilesystem();
        $serializer = $serializer ?: $this->newSerializer();
        $storage = new SingleFileStorage($files, $serializer);

        if ($autoPersist) {
//             $storage->onAfter('offsetSet', function () use ($storage) { $storage->persist(); });
//             $storage->onAfter('offsetUnset', function () use ($storage) { $storage->persist(); });
        }
        
        return $storage;
    }

    protected function newSerializer()
    {
        return new Serializer();
    }
}

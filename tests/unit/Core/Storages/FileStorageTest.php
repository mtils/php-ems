<?php

namespace Ems\Core\Storages;


use Ems\Testing\FilesystemMethods;
use Ems\Contracts\Core\Storage;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Filesystem;
use Ems\Core\LocalFilesystem;
use Ems\Core\Serializer;
use Ems\Core\Url;

class FileStorageTest extends \Ems\TestCase
{
    use FilesystemMethods;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(Storage::class, $this->newStorage());
    }

    public function test_getUrl_returns_setted_url()
    {
        $storage = $this->newStorage();
        $url = new Url('/home/michael');
        $this->assertSame($storage, $storage->setUrl($url));
        $this->assertSame($url, $storage->getUrl());
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

    public function test_purge_empties_storage()
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
        $storage2->purge();

        $this->assertFalse(isset($storage['bar']));
        $this->assertFalse(isset($storage['a']));
    }

    public function test_isset_triggers_load()
    {
        $storage = $this->newStorage();
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $this->assertFalse(isset($storage['foo']));
    }

    protected function newStorage(Filesystem $files=null, SerializerContract $serializer=null)
    {
        $files = $files ?: $this->newFilesystem();
        $serializer = $serializer ?: $this->newSerializer();
        return new FileStorage($files, $serializer);
    }

    protected function newSerializer()
    {
        return new Serializer();
    }
}

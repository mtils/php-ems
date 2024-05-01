<?php

namespace Ems\Core\Storages;


use Ems\Contracts\Core\Errors\ConfigurationError;
use Ems\Contracts\Core\Errors\DataCorruption;
use Ems\Contracts\Core\Errors\UnSupported;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Storage as StorageContract;
use Ems\Core\Serializer;
use Ems\Core\Url;
use Ems\Testing\FilesystemMethods;
use RuntimeException;

class FileStorageTest extends \Ems\TestCase
{
    use FilesystemMethods;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(StorageContract::class, $this->newStorage());
    }

    public function test_isBuffered_returns_false()
    {
        $this->assertFalse($this->newStorage()->isBuffered());
    }

    public function test_persist_just_returns_true()
    {
        $this->assertTrue($this->newStorage()->persist());
    }

    public function test_getUrl_returns_previous_set_url()
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
        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);
    }

    public function test_persist_with_unsupported_key_throws_exception()
    {
        $this->expectException(UnSupported::class);
        $storage = $this->newStorage();
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo$%%%/%§'] = 'bar';
    }

    public function test_persist_without_url_throws_exception()
    {
        $this->expectException(
            ConfigurationError::class
        );
        $storage = $this->newStorage();
        $url = new Url($this->tempFileName());

        $storage['foo'] = 'bar';

    }

    public function test_unwritable_directory_throws_exception()
    {
        $this->expectException(RuntimeException::class);
        $url = new Url('/proc/test');
        $storage = $this->newStorage()->setUrl($url);

        $storage['foo'] = 'bar';

    }

    public function test_persist_without_checksum_and_return_value()
    {
        $storage = $this->newStorage()->setOption('checksum_method', '');
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        unset($storage);

        $storage2 = $this->newStorage()->setOption('checksum_method', '');
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

        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);

        unset($storage2['a']);
        unset($storage2);


        $storage3 = $this->newStorage()->setUrl($url);
        $this->assertEquals('bar', $storage3['foo']);
        $this->assertFalse(isset($storage3['a']));
    }

    public function test_offsetUnset_does_nothing_if_directory_does_not_exist()
    {

        $fileSystem = $this->mock(Filesystem::class);
        $url = new Url($this->tempFileName());
        $storage = $this->newStorage(null, $fileSystem);
        $storage->setUrl($url);

        $fileSystem->shouldReceive('isDirectory')
                   ->andReturn(false);
        unset($storage['foo']);

    }

    public function test_persist_and_return_value_without_checksum()
    {
        $storage = $this->newStorage(null, null, false);
        $storage->setOption('checksum_method', '');
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';

        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);
    }

    public function test_persist_throws_exception_if_checksum_failed()
    {
        $this->expectException(DataCorruption::class);
        $storage = $this->newStorage(null, null, false);

        $storage->createChecksumBy(function ($method, $data) {
            return substr(md5(microtime()), rand(0, 26), 5);
        });

        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';

        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);
    }

    public function test_clear_empties_storage()
    {
        $storage = $this->newStorage(null, null, false);
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);
        $storage2->clear();

        $this->assertFalse(isset($storage['bar']));
        $this->assertFalse(isset($storage['a']));
    }

    public function test_purge_empties_storage()
    {
        $storage = $this->newStorage(null, null, false);
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);
        $storage2->purge();

        $this->assertFalse(isset($storage['bar']));
        $this->assertFalse(isset($storage['a']));
    }

    public function test_clear_with_passed_keys()
    {
        $storage = $this->newStorage(null, null, false);
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $storage['foo'] = 'bar';
        $storage['a'] = 'b';
        unset($storage);

        $storage2 = $this->newStorage();
        $storage2->setUrl($url);
        $this->assertEquals('bar', $storage2['foo']);
        $this->assertEquals('b', $storage2['a']);

        $this->assertSame($storage2, $storage2->clear([]));
        $this->assertSame($storage2, $storage2->clear(['bar']));

        $this->assertFalse(isset($storage2['bar']));
        $this->assertTrue(isset($storage2['a']));
    }

    public function test_keys_returns_empty_list_if_dir_not_found()
    {

        $fileSystem = $this->mock(Filesystem::class);
        $url = new Url($this->tempFileName());
        $storage = $this->newStorage(null, $fileSystem);
        $storage->setUrl($url);

        $fileSystem->shouldReceive('isDirectory')
                   ->andReturn(false);
        $this->assertCount(0, $storage->keys());

    }

    public function test_keys_returns_filenames()
    {
        $storage = $this->newStorage(null, null, false);
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);

        $data = [
            'foo' => 'bar',
            'a'   => 'b',
            'c'   => 'd',
            'e'   => 'f'
        ];

        foreach ($data as $key=>$value) {
            $storage[$key] = $value;
        }

        $keys = $storage->keys();
        $hit = false;
        foreach ($data as $key=>$value) {
            $this->assertTrue($keys->contains($key));
            $hit = true;
        }
        $this->assertTrue($hit, 'Empty keys from storage');
    }

    public function test_keys_returns_only_files_of_serializer()
    {
        $fileSystem = $this->mock(Filesystem::class);
        $storage = $this->newStorage(null, $fileSystem, false)->setOption('checksum_method', '');
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);

        $data = [
            'foo' => 'bar',
            'a'   => 'b',
            'c'   => 'd',
            'e'   => 'f'
        ];

        $fileSystem->shouldReceive('write');
        $fileSystem->shouldReceive('isDirectory')->andReturn(true);
        $fileSystem->shouldReceive('contents')->andReturn('foo');

        $fileSystem->shouldReceive('listDirectory')
                   ->andReturn(['a', 'b', 'c']);

        $fileSystem->shouldReceive('extension')
                   ->andReturn('docx');

        $this->assertEmpty($storage->keys());

    }

    public function test_keys_returns_no_directories()
    {
        $fileSystem = $this->mock(Filesystem::class);
        $storage = $this->newStorage(null, $fileSystem, false)->setOption('checksum_method', '');
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);

        $data = [
            'foo' => 'bar',
            'a'   => 'b',
            'c'   => 'd',
            'e'   => 'f'
        ];

        $fileSystem->shouldReceive('write');
        $fileSystem->shouldReceive('isDirectory')->andReturn(true);
        $fileSystem->shouldReceive('contents')->andReturn('phpdata');

        $fileSystem->shouldReceive('listDirectory')
                   ->andReturn(['a', 'b', 'c']);

        $fileSystem->shouldReceive('extension')
                   ->andReturn('phpdata');

        $this->assertEmpty($storage->keys());

    }

    public function test_toArray_returns_all_data()
    {
        $storage = $this->newStorage(null, null, false);
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);

        $data = [
            'foo' => 'bar',
            'a'   => 'b',
            'c'   => 'd',
            'e'   => 'f'
        ];

        foreach ($data as $key=>$value) {
            $storage[$key] = $value;
        }
        ksort($data);
        $storageArray = $storage->toArray();
        ksort($storageArray);

        $this->assertEquals($data, $storageArray);
    }

    public function test_isset_triggers_load()
    {
        $storage = $this->newStorage(null, null, false);
        $url = new Url($this->tempFileName());
        $storage->setUrl($url);
        $this->assertFalse(isset($storage['foo']));
    }

    protected function newStorage(SerializerContract $serializer=null, Filesystem $files=null)
    {
        $serializer = $serializer ?: $this->newSerializer();
        $files = $files ?: $this->newFilesystem();
        return new FileStorage($serializer, $files);
    }

    protected function newSerializer()
    {
        return new Serializer();
    }
}

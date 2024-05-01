<?php

namespace Ems\Core\Storages;

use Ems\Contracts\Core\Storage as StorageContract;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\Serializer;
use Ems\Contracts\Core\MimetypeProvider;
use Ems\Core\LocalFilesystem;
use Ems\Core\Serializer\JsonSerializer;
use Ems\Core\Serializer as PHPDataSerializer;
use Ems\Core\ManualMimetypeProvider;
use Ems\Core\Url;
use Ems\Testing\LoggingCallable;
use Ems\Testing\FilesystemMethods;

class NestedFileStorageTest extends \Ems\IntegrationTest
{
    use FilesystemMethods;

    protected $shouldPurgeTempFiles = false;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(StorageContract::class, $this->newStorage());
    }

    public function test_offsetExists_throws_exception_if_url_not_assigned()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnconfiguredException::class
        );
        $this->newStorage()->offsetExists('foo');
    }

    public function test_isBuffered_returns_true()
    {
        $this->assertTrue($this->newStorage()->isBuffered());
    }

    public function test_storageType_returns_filesystem()
    {
        $this->assertEquals('filesystem', $this->newStorage()->storageType());
    }

    public function test_get_serialize_options()
    {
        $this->assertTrue($this->newStorage()->getSerializeOptions()[JsonSerializer::PRETTY]);
    }

    public function test_get_deSerialize_options()
    {
        $storage = $this->newStorage();
        $this->assertSame($storage, $storage->setDeserializeOptions([JsonSerializer::AS_ARRAY=>true]));
        $this->assertTrue($this->newStorage()->getDeserializeOptions()[JsonSerializer::AS_ARRAY]);
    }

    public function test_offsetExists_returns_false_if_file_not_found()
    {
        $dirName = $this->tempDirName();
        $url = new Url($dirName);
        $storage = $this->newStorage()->setUrl($url);
        $this->assertFalse(isset($storage['foo']));
    }

    public function test_offsetSet_creates_cache_file()
    {

        $data = ['foo' => 'bar'];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $storage['de'] = ['foo' => 'bar'];

        $this->assertTrue(isset($storage['de']));
        $this->assertEquals($data, $storage['de']);
        $this->assertTrue($storage->persist());

        unset($storage);
        $storage2 = $this->newStorage()->setUrl($url);
        $this->assertEquals($data, $storage2['de']);
    }

    public function test_persist_throws_exception_if_directory_not_creatable()
    {
        $this->expectException(\RuntimeException::class);

        $data = ['foo' => 'bar'];

        $dirName = $this->tempDirName();
        $url = new Url('/proc/test');

        $storage = $this->newStorage()->setUrl($url);
        $storage['de'] = ['foo' => 'bar'];

    }

    public function test_filesystem_incompatible_key_throws_exception()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );

        $data = ['foo' => 'bar'];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $storage['$$%5%3§§3§3'] = ['foo' => 'bar'];

    }

    public function test_setNestingLevel_too_high_throws_exception()
    {
        $this->expectException(\OutOfBoundsException::class);

        $data = ['foo' => 'bar'];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $this->assertEquals(0, $storage->getNestingLevel());
        $storage->setNestingLevel(10);
    }

    public function test_setNestingLevel_after_file_access_throws_exception()
    {
        $this->expectException(\BadMethodCallException::class);

        $data = ['foo' => 'bar'];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $this->assertEquals(0, $storage->getNestingLevel());

        $storage['de'] = ['foo' => 'bar'];

        $this->assertTrue(isset($storage['de']));

        $storage->setNestingLevel(1);

    }

    public function test_offsetSet_creates_cache_file_with_nesting()
    {

        $data = ['foo' => 'bar'];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $this->assertEquals(0, $storage->getNestingLevel());
        $this->assertSame($storage, $storage->setNestingLevel(1));
        $this->assertSame($storage, $storage->setNestingLevel(1));

        $storage['de.formats'] = ['foo' => 'bar'];

        $this->assertTrue(isset($storage['de.formats']));
        $this->assertEquals($data, $storage['de.formats']);
        $this->assertEquals('bar', $storage['de.formats.foo']);
        $this->assertTrue($storage->persist());

        unset($storage);
        $storage2 = $this->newStorage()->setUrl($url)->setNestingLevel(1);
        $this->assertEquals($data, $storage2['de.formats']);
        $this->assertEquals('bar', $storage2['de.formats.foo']);
    }

    public function test_purge_with_nesting()
    {

        $data = ['foo' => 'bar'];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $this->assertEquals(0, $storage->getNestingLevel());
        $this->assertSame($storage, $storage->setNestingLevel(1));
        $this->assertSame($storage, $storage->setNestingLevel(1));

        $storage['de.formats'] = ['foo' => 'bar'];

        $this->assertTrue(isset($storage['de.formats']));
        $this->assertEquals($data, $storage['de.formats']);
        $this->assertEquals('bar', $storage['de.formats.foo']);
        $this->assertTrue($storage->persist());

        unset($storage);
        $storage2 = $this->newStorage()->setUrl($url)->setNestingLevel(1);
        $this->assertEquals($data, $storage2['de.formats']);
        $this->assertEquals('bar', $storage2['de.formats.foo']);

        $this->assertTrue($storage2->purge());

    }

    public function test_purge_without_written_data()
    {

        $data = ['foo' => 'bar'];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $this->assertEquals(0, $storage->getNestingLevel());
        $this->assertSame($storage, $storage->setNestingLevel(1));
        $this->assertSame($storage, $storage->setNestingLevel(1));

        $storage['de.formats'] = ['foo' => 'bar'];

        $this->assertTrue(isset($storage['de.formats']));
        $this->assertEquals($data, $storage['de.formats']);
        $this->assertEquals('bar', $storage['de.formats.foo']);

        $this->assertFalse($storage->purge());

    }

    public function test_key_with_less_segments_than_nesting_level_throws_exception()
    {
        $this->expectException(\Ems\Core\Exceptions\KeyLengthException::class);

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url)->setNestingLevel(1);
        isset($storage['de']);
    }

    public function test_offsetSet_creates_cache_file_from_nested_key()
    {

        $data = [
            'foo.bar'=> 'hello'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $this->assertFalse(isset($storage['de.foo.bar']));

        $storage['de.foo.bar'] = 'hello';
        $this->assertTrue(isset($storage['de.foo.bar']));

        $this->assertTrue(isset($storage['de']));
        $this->assertEquals($data, $storage['de']);

        $this->assertTrue($storage->persist());

        unset($storage);
        $storage2 = $this->newStorage()->setUrl($url);
        $this->assertEquals($data, $storage2['de']);
        $this->assertTrue(isset($storage2['de.foo.bar']));
        $this->assertEquals('hello', $storage2['de.foo.bar']);
    }

    public function test_offsetSet_throws_exception_if_root_is_no_array()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\Unsupported::class);

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $storage['de'] = 'Hiho';
    }

    public function test_offsetUnset_removes_key()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $storage['de'] = $data;

        $this->assertTrue(isset($storage['de']));
        $this->assertEquals($data, $storage['de']);
        unset($storage['de.foo']);
        $this->assertEquals(['baz'=>'boing'], $storage['de']);

        $storage->persist();
        unset($storage);
        $storage2 = $this->newStorage()->setUrl($url);
        $this->assertEquals(['baz'=>'boing'], $storage2['de']);
    }

    public function test_clear_removes_keys()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $storage['de'] = $data;

        $this->assertTrue(isset($storage['de']));
        $this->assertEquals($data, $storage['de']);
        $storage->clear(['de.foo']);
        $this->assertEquals(['baz'=>'boing'], $storage['de']);

        $storage->persist();
        unset($storage);
        $storage2 = $this->newStorage()->setUrl($url);
        $this->assertEquals(['baz'=>'boing'], $storage2['de']);
    }

    public function test_clear_removes_nothing_on_empty_keys_array()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $storage['de'] = $data;

        $this->assertTrue(isset($storage['de']));
        $this->assertEquals($data, $storage['de']);
        $storage->clear([]);
        $this->assertEquals($data, $storage['de']);
    }

    public function test_offsetUnset_removes_complete_file()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $storage['de'] = $data;

        $this->assertTrue(isset($storage['de']));
        $this->assertEquals($data, $storage['de']);

        unset($storage['de']);
        $this->assertFalse(isset($storage['de']));

        $storage->persist();

        $this->assertFalse(isset($storage['de']));


    }


    public function test_offsetSet_creates_file_for_content()
    {
        $storage = $this->newStorage();
    }

    public function test_keys_returns_all_keys()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $storage['de'] = $data;
        $storage['en'] = $data;
        $storage['fr'] = $data;

        $storage->persist();

        $this->assertEquals([
            'de',
            'de.foo',
            'de.baz',
            'en',
            'en.foo',
            'en.baz',
            'fr',
            'fr.foo',
            'fr.baz',
        ], $storage->keys()->getSource());


    }

    public function test_key_with_nesting_level()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url)->setNestingLevel(1);
        $storage['de.formats'] = $data;
        $storage['en.formats'] = $data;
        $storage['fr.formats'] = $data;

        $storage->persist();

        $this->assertEquals([
            'de.formats',
            'de.formats.foo',
            'de.formats.baz',
            'en.formats',
            'en.formats.foo',
            'en.formats.baz',
            'fr.formats',
            'fr.formats.foo',
            'fr.formats.baz',
        ], $storage->keys()->getSource());

        // Just to test the keyPrefix cache
        $this->assertEquals([
            'de.formats',
            'de.formats.foo',
            'de.formats.baz',
            'en.formats',
            'en.formats.foo',
            'en.formats.baz',
            'fr.formats',
            'fr.formats.foo',
            'fr.formats.baz',
        ], $storage->keys()->getSource());


    }

    public function test_toArray_returns_all_data()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);


        $storage = $this->newStorage()->setUrl($url);

        $awaited = [];
        foreach (['de', 'en', 'fr'] as $key) {
            $storage[$key] = $data;
            $awaited[$key] = $data;
        }

        $this->assertEquals($awaited, $storage->toArray());

        $storage->persist();

        $storage2 = $this->newStorage()->setUrl($url);

        $this->assertEquals($awaited, $storage2->toArray());

    }

    public function test_toArray_returns_all_data_with_nested_key()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $prefix = 'lang';

        $p = function ($key) use ($prefix) {
            return "$prefix.$key";
        };

        $dirName = $this->tempDirName();
        $url = new Url($dirName);


        $storage = $this->newStorage()->setUrl($url);

        $awaited = [$prefix => []];
        foreach (['de', 'en', 'fr'] as $key) {
            $storage[$p($key)] = $data;
            $awaited[$prefix][$key] = $data;
        }

        $storage->persist();

        $this->assertEquals($awaited, $storage->toArray());

        $storage2 = $this->newStorage()->setUrl($url);

        $this->assertEquals($awaited, $storage2->toArray());


    }

    public function test_offsetExists_works_with_nesting_level1()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $prefix = 'lang';

        $p = function ($key) use ($prefix) {
            return "$prefix.$key";
        };

        $dirName = $this->tempDirName();
        $url = new Url($dirName);


        $storage = $this->newStorage()->setUrl($url)->setNestingLevel(1);

        $awaited = [];
        foreach (['de', 'en', 'fr'] as $key) {
            $storage[$p($key)] = $data;
            $awaited[$p($key)] = $data;
        }

        $storage->persist();

        $this->assertEquals($awaited, $storage->toArray());

        $storage2 = $this->newStorage()->setUrl($url)->setNestingLevel(1);

        $this->assertTrue(isset($storage2['lang.de']));
        $this->assertTrue(isset($storage2['lang.en']));
        $this->assertTrue(isset($storage2['lang.fr']));
        $this->assertFalse(isset($storage2['lang.cz']));
        $this->assertTrue(isset($storage2['lang.de.foo']));
        $this->assertFalse(isset($storage2['lang.en.bla']));




    }

    public function test_toArray_returns_all_data_with_nesting_level1()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $prefix = 'lang';

        $p = function ($key) use ($prefix) {
            return "$prefix.$key";
        };

        $dirName = $this->tempDirName();
        $url = new Url($dirName);


        $storage = $this->newStorage()->setUrl($url)->setNestingLevel(1);

        $awaited = [];
        foreach (['de', 'en', 'fr'] as $key) {
            $storage[$p($key)] = $data;
            $awaited[$p($key)] = $data;
        }

        $storage->persist();

        $this->assertEquals($awaited, $storage->toArray());

        $storage2 = $this->newStorage()->setUrl($url)->setNestingLevel(1);

        $this->assertEquals($awaited, $storage2->toArray());

        $storage2["$prefix.es"] = $data;
        $awaited["$prefix.es"] = $data;

        $this->assertEquals($awaited, $storage2->toArray());

        $storage2->persist();

        $storage3 = $this->newStorage()->setUrl($url)->setNestingLevel(1);
        $this->assertEquals($awaited, $storage3->toArray());

    }

    public function test_toArray_returns_all_data_with_nesting_level2()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $prefix = 'lang.messages';

        $p = function ($key) use ($prefix) {
            return "$prefix.$key";
        };

        $dirName = $this->tempDirName();
        $url = new Url($dirName);


        $storage = $this->newStorage()->setUrl($url)->setNestingLevel(2);

        $awaited = [];
        foreach (['de', 'en', 'fr'] as $key) {
            $storage[$p($key)] = $data;
            $awaited[$p($key)] = $data;
        }

        $storage->persist();

        $this->assertEquals($awaited, $storage->toArray());

        $storage2 = $this->newStorage()->setUrl($url)->setNestingLevel(2);

        $this->assertEquals($awaited, $storage2->toArray());

        $storage2["$prefix.es"] = $data;
        $awaited["$prefix.es"] = $data;

        $this->assertEquals($awaited, $storage2->toArray());

        $storage2->persist();

        $storage3 = $this->newStorage()->setUrl($url)->setNestingLevel(2);
        $this->assertEquals($awaited, $storage3->toArray());

    }

    public function test_toArray_returns_all_data_with_nesting_level2_and_php_serializer()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $prefix = 'lang.messages';

        $p = function ($key) use ($prefix) {
            return "$prefix.$key";
        };

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $serializer = new PHPDataSerializer;

        $storage = $this->newStorage(null, $serializer)->setUrl($url)->setNestingLevel(2);

        $awaited = [];
        foreach (['de', 'en', 'fr'] as $key) {
            $storage[$p($key)] = $data;
            $awaited[$p($key)] = $data;
        }

        $storage->persist();

        $this->assertEquals($awaited, $storage->toArray());

        $storage2 = $this->newStorage(null, $serializer)->setUrl($url)->setNestingLevel(2);

        $this->assertEquals($awaited, $storage2->toArray());

        $storage2["$prefix.es"] = $data;
        $awaited["$prefix.es"] = $data;

        $this->assertEquals($awaited, $storage2->toArray());

        $storage2->persist();

        $storage3 = $this->newStorage(null, $serializer)->setUrl($url)->setNestingLevel(2);
        $this->assertEquals($awaited, $storage3->toArray());

    }

    public function test_createFileStorageBy_callable_is_used()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);

        $creator = new LoggingCallable(function () {
            throw new \LogicException;
        });

        $storage->createFileStorageBy($creator);

        try {
            $value = $storage['bla'];
            $this->fail('The custom fileStorageCreator was not called');
        } catch (\LogicException $e) {
            $this->assertInstanceOf(Filesystem::class, $creator->arg(0));
            $this->assertInstanceOf(Serializer::class, $creator->arg(1));
            $this->assertInstanceOf(MimetypeProvider::class, $creator->arg(2));
        }

    }

    public function test_clear_clears_all_data()
    {

        $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $storage = $this->newStorage()->setUrl($url);
        $this->assertSame($url, $storage->getUrl());
        $storage['de'] = $data;

        $this->assertTrue(isset($storage['de.foo']));
        $this->assertTrue(isset($storage['de.baz']));
        $this->assertEquals($data, $storage['de']);

        $this->assertSame($storage, $storage->clear());
        $this->assertFalse(isset($storage['de.foo']));
        $this->assertFalse(isset($storage['de.baz']));

        $this->assertEquals([], $storage['de']);

    }

    public function test_purge_deletes_all_data()
    {
         $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);


        $storage = $this->newStorage()->setUrl($url);

        $awaited = [];
        foreach (['de', 'en', 'fr'] as $key) {
            $storage[$key] = $data;
            $awaited[$key] = $data;
        }

        $storage->persist();

        $this->assertEquals($awaited, $storage->toArray());

        $this->assertTrue($storage->purge());

        $this->assertEquals([], $storage->toArray());
    }

    public function test_purge_with_keys()
    {
         $data = [
            'foo' => 'bar',
            'baz' => 'boing'
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);


        $storage = $this->newStorage()->setUrl($url);

        $awaited = [];
        foreach (['de', 'en', 'fr'] as $key) {
            $storage[$key] = $data;
            $awaited[$key] = $data;
        }

        $storage->persist();

        $this->assertEquals($awaited, $storage->toArray());

        $this->assertFalse($storage->purge([]));
        $this->assertTrue($storage->purge(['de.foo', 'en.foo']));

        unset($awaited['de']['foo']);
        unset($awaited['en']['foo']);
        $this->assertEquals($awaited, $storage->toArray());
    }

    protected function newStorage(FileSystem $fs=null, Serializer $serializer=null, MimetypeProvider $mimetypes=null)
    {
        $fs = $fs ?: new LocalFilesystem;
        $serializer = $serializer ?: new JsonSerializer;
        $mimetypes = $mimetypes ?: new ManualMimeTypeProvider;

        return (new NestedFileStorage($fs, $serializer, $mimetypes))
                ->setSerializeOptions([JsonSerializer::PRETTY=>true]);

    }
}

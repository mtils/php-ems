<?php
/**
 *  * Created by mtils on 11.06.19 at 14:20.
 **/

namespace Ems\Core\Storages;


use Ems\Contracts\Core\Storage;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OneItemProxyStorageTest extends TestCase
{
    #[Test] public function it_implements_storage_interface()
    {
        $this->assertInstanceOf(Storage::class, $this->newStorage());
    }

    #[Test] public function offsetExists_returns_right_value()
    {
        $storage = $this->newStorage();
        $this->assertFalse(isset($storage['foo']));

        $storage['foo'] = ['my' => 'funny bar'];

        $this->assertTrue(isset($storage['foo']));
    }

    #[Test] public function offsetGet_returns_right_value()
    {
        $storage = $this->newStorage();

        $data = ['my' => 'funny bar'];

        $storage['foo'] = $data;

        $this->assertEquals($data, $storage['foo']);
    }

    #[Test] public function offsetGet_throws_exception_if_key_not_found()
    {
        $this->expectException(
            \Ems\Core\Exceptions\KeyNotFoundException::class
        );
        echo $this->newStorage()['foo'];
    }

    #[Test] public function offsetSet_sets_value_on_UnPushable_Storage()
    {
        $storage = $this->newStorage();

        $data = ['my' => 'funny bar'];

        $storage['foo'] = $data;

        $this->assertEquals($data, $storage['foo']);

        $data2 = ['my' => 'funny valentine', 'your' => 'birthday'];

        $storage['foo'] = $data2;

        $this->assertEquals($data2, $storage['foo']);

        $storage['test'] = 'it';

        $this->assertEquals($data2, $storage['foo']);
        $this->assertEquals('it', $storage['test']);
    }

    #[Test] public function offsetSet_sets_value_on_Pushable_Storage()
    {
        $storage = $this->newStorage($this->newPushableStorage());

        $data = ['my' => 'funny bar'];

        $storage['foo'] = $data;

        $this->assertEquals($data, $storage['foo']);

        $data2 = ['my' => 'funny valentine', 'your' => 'birthday'];

        $storage['foo'] = $data2;

        $this->assertEquals($data2, $storage['foo']);

        $storage['test'] = 'it';

        $this->assertEquals($data2, $storage['foo']);
        $this->assertEquals('it', $storage['test']);
    }

    #[Test] public function offsetUnset_removes_set_values()
    {
        $storage = $this->newStorage();

        $data = ['my' => 'funny bar'];

        $storage['foo'] = $data;

        $this->assertEquals($data, $storage['foo']);

        unset($storage['foo']);

        $this->assertFalse(isset($storage['foo']));
    }

    #[Test] public function offsetUnset_does_nothing_if_data_not_stored()
    {
        $storage = $this->newStorage();
        unset($storage['foo']);
    }

    #[Test] public function offsetUnset_throws_exception_if_key_not_found()
    {
        $this->expectException(
            \Ems\Core\Exceptions\KeyNotFoundException::class
        );
        $storage = $this->newStorage();
        $data = ['my' => 'funny bar'];

        $storage['foo'] = $data;
        unset($storage['bar']);
    }

    #[Test] public function purge_does_nothing_if_empty_array_passed()
    {
        $storage = $this->newStorage();
        $data = ['my' => 'funny bar'];
        $storage['foo'] = $data;

        $storage->purge([]);

        $this->assertEquals($data, $storage['foo']);
    }

    #[Test] public function purge_removes_all_data()
    {
        $storage = $this->newStorage();

        $data = [
            'name' => 'Uncle Sam',
            'password' => 123456,
            'age'      => 42
        ];

        foreach($data as $key=>$value) {
            $storage[$key] = $value;
        }

        foreach ($data as $key=>$value) {
            $this->assertEquals($value, $storage[$key]);
        }

        $storage->purge();

        $this->assertSame([], $storage->toArray());
    }

    #[Test] public function purge_removes_passed_keys()
    {
        $storage = $this->newStorage();

        $data = [
            'name' => 'Uncle Sam',
            'password' => 123456,
            'age'      => 42
        ];

        foreach($data as $key=>$value) {
            $storage[$key] = $value;
        }

        foreach ($data as $key=>$value) {
            $this->assertEquals($value, $storage[$key]);
        }

        $storage->purge(['name', 'age']);

        $this->assertEquals(['password' => 123456], $storage->toArray());
    }

    #[Test] public function clear_removes_passed_keys()
    {
        $storage = $this->newStorage();

        $data = [
            'name' => 'Uncle Sam',
            'password' => 123456,
            'age'      => 42
        ];

        foreach($data as $key=>$value) {
            $storage[$key] = $value;
        }

        foreach ($data as $key=>$value) {
            $this->assertEquals($value, $storage[$key]);
        }

        $storage->clear(['name', 'age']);

        $this->assertEquals(['password' => 123456], $storage->toArray());
    }

    #[Test] public function keys_returns_all_keys()
    {
        $storage = $this->newStorage();

        $data = [
            'name' => 'Uncle Sam',
            'password' => 123456,
            'age'      => 42
        ];

        foreach($data as $key=>$value) {
            $storage[$key] = $value;
        }

        $this->assertEquals('name password age', (string)$storage->keys());
    }

    #[Test] public function keys_returns_empty_list_if_no_keys()
    {
        $storage = $this->newStorage();

        $this->assertCount(0, $storage->keys());
    }

    #[Test] public function purge_does_nothing_if_no_data_stored()
    {
        $this->newStorage()->purge();
    }

    #[Test] public function setFictitiousId_sets_is()
    {
        $this->assertEquals('foo', $this->newStorage()->setFictitiousId('foo')->getFictitiousId());
    }

    protected function newStorage(Storage $base=null)
    {
        return new OneItemProxyStorage($base ?: $this->newBaseStorage());
    }

    protected function newBaseStorage()
    {
        return new ArrayStorage();
    }

    protected function newPushableStorage()
    {
        return new PushableProxyStorage($this->newBaseStorage());
    }
}
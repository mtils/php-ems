<?php
/**
 *  * Created by mtils on 11.04.20 at 07:23.
 **/

namespace Ems\Model;


use Ems\Contracts\Core\Url;
use Ems\Contracts\Model\Relationship;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ClassMapTest extends TestCase
{
    #[Test] public function it_instantiates()
    {
        $this->assertInstanceOf(ClassMap::class, $this->newMap());
    }

    #[Test] public function get_and_set_StorageUrl()
    {
        $address = 'database://default';
        $map = $this->newMap();
        $this->assertNull($map->getStorageUrl());
        $this->assertSame($map, $map->setStorageUrl($address));
        $url = $map->getStorageUrl();
        $this->assertInstanceOf(Url::class, $url);
        $this->assertEquals($address, "$url");
    }

    #[Test] public function get_and_set_ormClass()
    {
        $class = Url::class;
        $map = $this->newMap();
        $this->assertSame('', $map->getOrmClass());
        $this->assertSame($map, $map->setOrmClass($class));
        $this->assertSame($class, $map->getOrmClass());
    }

    #[Test] public function get_and_set_storageName()
    {
        $name = 'users';
        $map = $this->newMap();
        $this->assertSame('', $map->getStorageName());
        $this->assertSame($map, $map->setStorageName($name));
        $this->assertSame($name, $map->getStorageName());
    }

    #[Test] public function get_and_set_primary_key()
    {
        $keys = ['id', 'email', 'password'];

        $map = $this->newMap();
        $this->assertSame('id', $map->getPrimaryKey());
        $this->assertSame($map, $map->setPrimaryKey($keys[1]));
        $this->assertSame($keys[1], $map->getPrimaryKey());

        $this->assertSame($map, $map->setPrimaryKey($keys));
        $this->assertSame($keys, $map->getPrimaryKey());
    }

    #[Test] public function get_and_set_keys()
    {
        $keys = ['id', 'email', 'password'];

        $map = $this->newMap();
        $this->assertSame([], $map->getKeys());
        $this->assertSame($map, $map->setKeys($keys));
        $this->assertSame($keys, $map->getKeys());
    }

    #[Test] public function get_and_set_relation()
    {
        $relation = new Relationship();
        $map = $this->newMap();
        $this->assertNull($map->getRelationship('files'));
        $this->assertSame($map, $map->setRelationship('files', $relation));
        $this->assertSame($relation, $map->getRelationship('files'));

    }

    #[Test] public function get_and_set_defaults()
    {
        $defaults = [
            'foo' => 'bar',
            'call' => 'x'
        ];
        $map = $this->newMap();
        $map->setDefaults($defaults);
        $this->assertEquals([
            'foo' => 'bar',
            'call' => 'x'
        ], $map->getDefaults());
    }

    #[Test] public function get_and_set_autoUpdates()
    {
        $defaults = [
            'foo' => 'bar',
            'call' => 'x'
        ];
        $map = $this->newMap();
        $map->setAutoUpdates($defaults);
        $this->assertEquals([
                                'foo' => 'bar',
                                'call' => 'x'
                            ], $map->getAutoUpdates());
    }

    #[Test] public function get_and_set_types()
    {
        $types = [
            'foo' => 'string',
            'bar' => 'int'
        ];
        $map = $this->newMap();
        $map->setType($types);
        $this->assertEquals($types['foo'], $map->getType('foo'));
        $this->assertEquals($types['bar'], $map->getType('bar'));

        $map = $this->newMap();
        $map->setType('price', 'float');
        $this->assertEquals('float', $map->getType('price'));
    }

    /**
     * @return ClassMap
     */
    protected function newMap()
    {
        return new ClassMap();
    }
}
<?php
/**
 *  * Created by mtils on 11.04.20 at 07:23.
 **/

namespace Ems\Model;


use Ems\Contracts\Core\Url;
use Ems\Contracts\Model\Relationship;
use Ems\TestCase;

class ClassMapTest extends TestCase
{
    /**
     * @test
     */
    public function it_instantiates()
    {
        $this->assertInstanceOf(ClassMap::class, $this->newMap());
    }

    /**
     * @test
     */
    public function get_and_set_StorageUrl()
    {
        $address = 'database://default';
        $map = $this->newMap();
        $this->assertNull($map->getStorageUrl());
        $this->assertSame($map, $map->setStorageUrl($address));
        $url = $map->getStorageUrl();
        $this->assertInstanceOf(Url::class, $url);
        $this->assertEquals($address, "$url");
    }

    /**
     * @test
     */
    public function get_and_set_ormClass()
    {
        $class = Url::class;
        $map = $this->newMap();
        $this->assertSame('', $map->getOrmClass());
        $this->assertSame($map, $map->setOrmClass($class));
        $this->assertSame($class, $map->getOrmClass());
    }

    /**
     * @test
     */
    public function get_and_set_storageName()
    {
        $name = 'users';
        $map = $this->newMap();
        $this->assertSame('', $map->getStorageName());
        $this->assertSame($map, $map->setStorageName($name));
        $this->assertSame($name, $map->getStorageName());
    }

    /**
     * @test
     */
    public function get_and_set_primary_key()
    {
        $keys = ['id', 'email', 'password'];

        $map = $this->newMap();
        $this->assertSame('id', $map->getPrimaryKey());
        $this->assertSame($map, $map->setPrimaryKey($keys[1]));
        $this->assertSame($keys[1], $map->getPrimaryKey());

        $this->assertSame($map, $map->setPrimaryKey($keys));
        $this->assertSame($keys, $map->getPrimaryKey());
    }

    /**
     * @test
     */
    public function get_and_set_keys()
    {
        $keys = ['id', 'email', 'password'];

        $map = $this->newMap();
        $this->assertSame([], $map->getKeys());
        $this->assertSame($map, $map->setKeys($keys));
        $this->assertSame($keys, $map->getKeys());
    }

    /**
     * @test
     */
    public function get_and_set_relation()
    {
        $relation = new Relationship();
        $map = $this->newMap();
        $this->assertNull($map->getRelationship('files'));
        $this->assertSame($map, $map->setRelationship('files', $relation));
        $this->assertSame($relation, $map->getRelationship('files'));

    }

    /**
     * @return ClassMap
     */
    protected function newMap()
    {
        return new ClassMap();
    }
}
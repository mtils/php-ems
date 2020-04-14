<?php
/**
 *  * Created by mtils on 11.04.20 at 07:23.
 **/

namespace Ems\Model;


use Ems\Contracts\Core\Url;
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
        $relation = new Relation();
        $map = $this->newMap();
        $this->assertNull($map->getRelation('files'));
        $this->assertSame($map, $map->setRelation('files', $relation));
        $this->assertSame($relation, $map->getRelation('files'));

    }

    /**
     * @return ClassMap
     */
    protected function newMap()
    {
        return new ClassMap();
    }
}
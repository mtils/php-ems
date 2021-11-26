<?php
/**
 *  * Created by mtils on 11.04.20 at 08:04.
 **/

namespace unit\Model;


use Ems\Contracts\Model\Relationship;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Model\ClassMap;
use Ems\Model\MapSchemaInspector;
use Ems\TestCase;
use Ems\TestOrm;
use Exception;
use Models\Contact;
use Models\File;
use Models\ProjectType;
use Models\User;
use stdClass;

class MapSchemaInspectorTest extends TestCase
{
    use TestOrm;

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(SchemaInspector::class, $this->make());
    }

    /**
     * @test
     */
    public function map_object()
    {
        $inspector = $this->make();
        $map = new ClassMap();
        $this->assertSame($inspector, $inspector->map(TestCase::class, $map));
        $this->assertSame($map, $inspector->getMap(TestCase::class));
    }

    /**
     * @test
     */
    public function map_closure()
    {
        $inspector = $this->make();
        $map = new ClassMap();
        $this->assertSame($inspector, $inspector->map(TestCase::class, function () use ($map) {
            return $map;
        }));
        $this->assertSame($map, $inspector->getMap(TestCase::class));
    }

    /**
     * @test
     */
    public function map_class()
    {
        $inspector = $this->make();
        $this->assertSame($inspector, $inspector->map(TestCase::class, MapSchemaInspectorTest_ClassMap::class));
        $this->assertInstanceOf(MapSchemaInspectorTest_ClassMap::class, $inspector->getMap(TestCase::class));
    }

    /**
     * @test
     * @expectedException \Ems\Contracts\Core\Exceptions\TypeException
     */
    public function map_unknown_type_throws_exception()
    {
        $inspector = $this->make();
        $inspector->map(TestCase::class, new stdClass());
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\HandlerNotFoundException
     */
    public function get_unknown_map_throws_exception()
    {
        $inspector = $this->make();
        $inspector->getMap(TestCase::class);
    }

    /**
     * @test
     */
    public function getters_are_forwarded_to_ClassMap()
    {
        $map = new ClassMap();
        $map->setOrmClass(TestCase::class)
            ->setStorageName('test-cases')
            ->setStorageUrl('rest://my-domain.de/api/v2')
            ->setKeys(['a', 'b', 'c'])
            ->setRelationship('errors', new Relationship())
            ->setDefaults(['a' => 'b'])
            ->setAutoUpdates(['c' => 'd'])
        ->setPrimaryKey('foo');

        $inspector = $this->make();
        $inspector->map(TestCase::class, $map);

        $this->assertEquals((string)$map->getStorageUrl(), $inspector->getStorageUrl(TestCase::class));
        $this->assertSame($map->getStorageName(), $inspector->getStorageName(TestCase::class));
        $this->assertSame($map->getKeys(), $inspector->getKeys(TestCase::class));
        $this->assertSame($map->getRelationship('errors'), $inspector->getRelationship(TestCase::class, 'errors'));
        $this->assertSame($map->getPrimaryKey(), $inspector->primaryKey(TestCase::class));
        $this->assertSame($map->getDefaults(), $inspector->getDefaults(TestCase::class));
        $this->assertSame($map->getAutoUpdates(), $inspector->getAutoUpdates(TestCase::class));
    }

    /**
    * @test
    */
    public function type_returns_string_on_existing_key()
    {
        $map = new ClassMap();
        $map->setOrmClass(TestCase::class)
            ->setStorageName('test-cases')
            ->setStorageUrl('rest://my-domain.de/api/v2')
            ->setKeys(['a', 'b', 'c'])
            ->setRelationship('errors', new Relationship());

        $inspector = $this->make();
        $inspector->map(TestCase::class, $map);

        $this->assertEquals('string', $inspector->type(TestCase::class, 'a'));
        $this->assertEquals('string', $inspector->type(TestCase::class, 'b'));
        $this->assertEquals('string', $inspector->type(TestCase::class, 'c'));
        $this->assertNull($inspector->type(TestCase::class, 'd'));
    }

    /**
     * @test
     */
    public function type_returns_type_on_relation()
    {
        $inspector = $this->newInspector();
        $this->assertEquals(Contact::class, $inspector->type(User::class, 'contact'));
        $this->assertEquals(User::class, $inspector->type(Contact::class, 'user'));
    }

    /**
     * @test
     */
    public function type_returns_null_on_unknown_class()
    {
        $inspector = $this->newInspector();
        $this->assertNull($inspector->type(Exception::class, 'contact'));
    }

    /**
     * @test
     */
    public function type_returns_null_on_unknown_relation()
    {
        $inspector = $this->newInspector();
        $this->assertNull($inspector->type(User::class, 'contacts'));
    }

    /**
     * @test
     */
    public function type_returns_type_on_nested_relation()
    {
        $inspector = $this->newInspector();
        $this->assertEquals(ProjectType::class, $inspector->type(User::class, 'projects.type'));
        $this->assertEquals(File::class.'[]', $inspector->type(User::class, 'projects.files'));
        $this->assertEquals('string', $inspector->type(User::class, 'projects.files.name'));
        $this->assertNull($inspector->type(User::class, 'projects.files.foo'));
    }

    /**
     * @return MapSchemaInspector
     */
    protected function make()
    {
        return new MapSchemaInspector();
    }

}

class MapSchemaInspectorTest_ClassMap extends ClassMap {};
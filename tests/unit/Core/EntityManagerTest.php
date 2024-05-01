<?php
/**
 *  * Created by mtils on 07.02.18 at 05:58.
 **/

namespace Ems\Core;


use Ems\Contracts\Core\EntityPointer;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\TestCase;
use Ems\Contracts\Core\EntityManager as EntityManagerContract;
use stdClass;

class EntityManagerTest extends TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(EntityManagerContract::class, $this->newManager());
    }

    public function test_provider_by_passing_instance()
    {
        $manager = $this->newManager();

        $provider = new GenericResourceProvider();

        $manager->setProvider(stdClass::class, $provider);

        $this->assertSame($provider, $manager->provider(stdClass::class));
    }

    public function test_provider_by_passing_callable()
    {
        $manager = $this->newManager();

        $provider = new GenericResourceProvider();

        $factory = function ($class) use ($provider) {
            $this->assertEquals(stdClass::class, $class);
            return $provider;
        };

        $manager->setProvider(stdClass::class, $factory);

        $this->assertSame($provider, $manager->provider(stdClass::class));
    }

    public function test_provider_by_passing_class()
    {
        $manager = $this->newManager();

        $manager->setProvider(stdClass::class, GenericResourceProvider::class);

        $this->assertInstanceOf(GenericResourceProvider::class, $manager->provider(stdClass::class));
    }

    public function test_setProvider_with_unknown_parameter_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $manager = $this->newManager();

        $manager->setProvider(stdClass::class, 42);

    }

    public function test_provider_for_unregistered_class_throws_exception()
    {
        $this->expectException(
            \Ems\Core\Exceptions\HandlerNotFoundException::class
        );
        $manager = $this->newManager();

        $manager->setProvider(stdClass::class, GenericResourceProvider::class);

        $manager->provider(static::class);
    }

    public function test_pointer_creates_no_pointer_when_no_provider_assigned()
    {
        $obj = new NamedObject(12, 'Bill');
        $this->assertNull($this->newManager()->pointer($obj));
    }

    public function test_pointer_creates_pointer_when_provider_assigned()
    {
        $obj = new NamedObject(12, 'Bill');
        $manager = $this->newManager();

        $manager->setProvider(NamedObject::class, new GenericResourceProvider());
        $pointer = $manager->pointer($obj);

        $this->assertInstanceOf(EntityPointer::class, $pointer);
        $this->assertEquals($obj->getId(), $pointer->id);
        $this->assertEquals(get_class($obj), $pointer->type);

    }

    public function test_pointer_creates_pointer_when_extension_assigned()
    {
        $obj = new NamedObject(12, 'Bill');
        $manager = $this->newManager();

        $manager->extend(NamedObject::class, function ($class, $id) {
            return new NamedObject($id);
        });

        $pointer = $manager->pointer($obj);

        $this->assertInstanceOf(EntityPointer::class, $pointer);
        $this->assertEquals($obj->getId(), $pointer->id);
        $this->assertEquals(get_class($obj), $pointer->type);

    }

    public function test_get_returns_object_by_provider()
    {
        $obj = new NamedObject(12, 'Bill');
        $provider = new GenericResourceProvider();
        $provider->add($obj);

        $manager = $this->newManager();

        $manager->setProvider(NamedObject::class, $provider);

        $this->assertSame($obj, $manager->get(NamedObject::class, 12));

        $this->assertSame($obj, $manager->get(new EntityPointer(NamedObject::class, 12)));

    }

    public function test_get_returns_object_by_extension()
    {
        $obj = new NamedObject(12, 'Bill');

        $manager = $this->newManager();

        $manager->extend(NamedObject::class, function ($class, $id) use ($obj) {
            $this->assertEquals($obj->getId(), $id);
            $this->assertEquals($class, get_class($obj));
            return $obj;
        });

        $this->assertSame($obj, $manager->get(NamedObject::class, 12));
        $this->assertSame($obj, $manager->getOrFail(NamedObject::class, 12));

        $this->assertSame($obj, $manager->get(new EntityPointer(NamedObject::class, 12)));
        $this->assertSame($obj, $manager->getOrFail(new EntityPointer(NamedObject::class, 12)));

    }

    public function test_get_returns_null()
    {
        $manager = $this->newManager();

        $this->assertNull($manager->get(NamedObject::class, 12));

    }

    public function test_get_returns_null_if_extension_throws_NotFound()
    {
        $manager = $this->newManager();

        $manager->extend(NamedObject::class, function ($class, $id) {
            $this->assertEquals(NamedObject::class, $class);
            $this->assertEquals(12, $id);

            throw new ResourceNotFoundException();
        });

        $this->assertNull($manager->get(NamedObject::class, 12));

    }

    public function test_getOrFail_throws_NotFound()
    {
        $this->expectException(
            \Ems\Core\Exceptions\ResourceNotFoundException::class
        );
        $manager = $this->newManager();

        $manager->getOrFail(NamedObject::class, 12);

    }

    public function test_only_passing_class_throws_exception()
    {
        $this->expectException(
            \Ems\Core\Exceptions\MissingArgumentException::class
        );
        $this->newManager()->get(static::class);
    }

    protected function newManager(callable $factory=null)
    {
        $manager = new EntityManager();
        if ($factory) {
            $manager->createObjectsBy($factory);
        }
        return $manager;
    }
}
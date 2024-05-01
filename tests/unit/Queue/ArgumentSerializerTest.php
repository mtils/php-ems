<?php
/**
 *  * Created by mtils on 08.02.18 at 06:32.
 **/

namespace Ems\Queue;


use Ems\Contracts\Core\EntityManager as EntityManagerContract;
use Ems\Contracts\Core\Serializer;
use Ems\Core\EntityManager;
use Ems\Core\NamedObject;
use Ems\TestCase;

class ArgumentSerializerTest extends TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(Serializer::class, $this->serializer());
        $this->assertNotEmpty($this->serializer()->mimeType());
    }

    public function test_serialize_does_not_serialize_scalars()
    {
        $serializer = $this->serializer();

        foreach ([12, 85.3, false, null] as $value) {
            $this->assertSame($value, $serializer->serialize($value));
        }
    }

    public function test_serialize_serializes_arrays()
    {
        $serializer = $this->serializer();

        $input = [12, 66, 'foo'];

        $this->assertEquals($input, $serializer->deserialize($serializer->serialize($input)));
    }

    public function test_serialize_serializes_by_entity_pointer()
    {
        $entityManager = new EntityManager();

        $entity = new NamedObject(12, 'Richard');

        $entityManager->extend(NamedObject::class, function ($class, $id) use ($entity) {
            $this->assertEquals(get_class($entity), $class);
            $this->assertEquals($entity->getId(), $id);
            return $entity;
        });

        $serializer = $this->serializer($entityManager);

        $serializedPointer = $serializer->serialize($entity);

        $this->assertSame($entity, $serializer->deserialize($serializedPointer));
    }

    public function test_serialize_throws_exception_if_object_not_supported()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $serializer = $this->serializer();
        $serializer->serialize(new ArgumentSerializerTest_Not_Serializable());
    }

    public function test_serialize_throws_exception_if_object_not_supported_by_entity_manager()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $serializer = $this->serializer();
        $serializer->serialize(new NamedObject(12));
    }

    public function test_encode_and_decode_empty_arguments()
    {
        $serializer = $this->serializer();
        $this->assertEquals([], $serializer->decode($serializer->encode([])));
    }

    public function test_encode_and_decode_arguments()
    {
        $entityManager = new EntityManager();

        $entity = new NamedObject(12, 'Richard');

        $entityManager->extend(NamedObject::class, function ($class, $id) use ($entity) {
            $this->assertEquals(get_class($entity), $class);
            $this->assertEquals($entity->getId(), $id);
            return $entity;
        });

        $serializer = $this->serializer($entityManager);

        $args = [
            12,
            $entity,
            'foo'
        ];

        $this->assertEquals($args, $serializer->decode($serializer->encode($args)));
    }

    protected function serializer(EntityManagerContract $entityManager=null)
    {
        $entityManager = $entityManager ?: new EntityManager();
        return new ArgumentSerializer($entityManager);
    }

}

class ArgumentSerializerTest_Not_Serializable
{}
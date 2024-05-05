<?php

namespace Ems\Core\Serializer;


use Ems\Contracts\Core\Errors\UnSupported;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Core\Exceptions\DataIntegrityException;
use Ems\Testing\Cheat;

use function function_exists;

class GDSerializerTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(SerializerContract::class, $this->newSerializer());
    }

    public function test_get_and_set_mimeType()
    {
        $serializer = $this->newSerializer();
        $this->assertSame($serializer, $serializer->setMimeType('image/gif'));
        $this->assertEquals('image/gif', $serializer->mimeType());
    }

    public function test_set_unsupported_mimetype_throws_exception()
    {
        $this->expectException(UnSupported::class);
        $serializer = $this->newSerializer();
        $serializer->setMimeType('image/maya-13-propietary-format');
    }

    public function test_serialize_and_deserialize_png_image()
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('No GD extension available');
        }
        $serializer = $this->newSerializer()->setMimeType('image/png');
        $resource = imagecreatetruecolor(60, 60);

        $blob = $serializer->serialize($resource);

        $resource2 = $serializer->deserialize($blob);

        $this->assertEquals('gd', strtolower(get_resource_type($resource2)));

    }

    public function test_serialize_and_deserialize_gif_image()
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('No GD extension available');
        }
        $serializer = $this->newSerializer()->setMimeType('image/gif');
        $resource = imagecreatetruecolor(60, 60);

        $blob = $serializer->serialize($resource);

        $resource2 = $serializer->deserialize($blob);

        $this->assertEquals('gd', strtolower(get_resource_type($resource2)));

    }

    public function test_serialize_and_deserialize_jpeg_image()
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('No GD extension available');
        }
        $serializer = $this->newSerializer()->setMimeType('image/jpeg');
        $resource = imagecreatetruecolor(60, 60);

        $blob = $serializer->serialize($resource);

        $resource2 = $serializer->deserialize($blob);

        $this->assertEquals('gd', strtolower(get_resource_type($resource2)));

    }

    public function test_serialize_unsupported_value_throws_exception()
    {
        $this->expectException(UnSupported::class);
        $serializer = $this->newSerializer()->setMimeType('image/png');

        $serializer->serialize('bob');
    }

    public function test_serialize_unsupported_resource_throws_exception()
    {
        $this->expectException(UnSupported::class);
        $serializer = $this->newSerializer()->setMimeType('image/png');

        $serializer->serialize(fopen(__FILE__, 'r'));
    }

    public function test_deserialize_invalid_data_throws_exception()
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('No GD extension available');
        }
        $this->expectException(
            DataIntegrityException::class
        );
        $serializer = $this->newSerializer()->setMimeType('image/png');

        $serializer->deserialize('hihihahaha');
    }

    protected function newSerializer(callable $errorGetter=null)
    {
        return new GDSerializer;
    }
}


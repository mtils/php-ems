<?php

namespace Ems\Core\Serializer;


use Ems\Contracts\Core\Errors\UnSupported;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Testing\Cheat;

class JsonSerializerTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(SerializerContract::class, $this->newSerializer());
    }

    public function test_mimeType_returns_string()
    {
        $mimeType = $this->newSerializer()->mimeType();
        $this->assertIsString($mimeType);
        $this->assertStringContainsString('/', $mimeType);
    }

    public function test_serialize_and_deserialize_valid_values()
    {
        $serializer = $this->newSerializer();

        $tests = [
            [],
            [1,2,3],
            ['foo' => 'bar', 'baz' => 'bu' ],
            ['float' => 4.5],
            ['abcdeöäüüpouioß']
        ];

        foreach ($tests as $test) {
            $serialized = $serializer->serialize($test);
            $this->assertIsString($serialized);
            $deserialized = $serializer->deserialize($serialized, [JsonSerializer::AS_ARRAY=>true]);
            $this->assertEquals($test, $deserialized);
        }
    }

    public function test_serialize_with_depth_2()
    {
        $serializer = $this->newSerializer();

        $test = [
            'name'      => 'Michael',
            'address'   => [
                'street' => 'Elm Str.'
            ]
        ];

        $awaited = (object)[
            'name'      => 'Michael'
        ];


        $serialized = $serializer->serialize($test);

        $this->assertIsString('string', $serialized);
        $deserialized = $serializer->deserialize($serialized, [JsonSerializer::DEPTH=>2]);

        $this->assertNull($deserialized);

    }

    public function test_serialize_pretty()
    {
        $serializer = $this->newSerializer();

        $test = [
            'name'      => 'Michael',
            'address'   => [
                'street' => 'Elm Str.'
            ]
        ];

        $awaited = (object)[
            'name'      => 'Michael'
        ];


        $serialized = $serializer->serialize($test, [JsonSerializer::PRETTY=>true]);
        $this->assertEquals(json_encode($test, JSON_PRETTY_PRINT), $serialized);

    }

    public function _test_serializing_of_resource_throws_exception()
    {
        $this->expectException(UnSupported::class);
        $serializer = $this->newSerializer();
        $res = opendir(sys_get_temp_dir());
        $serializer->serialize($res);
    }

    public function _test_deserializing_of_malformed_string_throws_exception_without_error()
    {
        $this->expectException(UnSupported::class);
        $hook = function () { return false; };
        $serializer = $this->newSerializer($hook);
        $serializer->deserialize('foo');
    }

    public function _test_deserializing_of_malformed_string_throws_exception()
    {
        $this->expectException(UnSupported::class);
        $serializer = $this->newSerializer();
        $serializer->deserialize('foo');
    }

    protected function newSerializer(callable $errorGetter=null)
    {
        return new JsonSerializer($errorGetter);
    }
}


<?php

namespace Ems\Core;


use Ems\Contracts\Core\Serializer as SerializerContract;

class SerializerTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(SerializerContract::class, $this->newSerializer());
    }

    public function test_serialize_and_deserialize_scalar_values()
    {
        $serializer = $this->newSerializer();

        $tests = [
            1,
            0,
            true,
            4.5,
            'abcdeöäüüpouioß'
        ];

        foreach ($tests as $test) {
            $serialized = $serializer->serialize($test);
            $this->assertInternalType('string', $serialized);
            $this->assertEquals($test, $serializer->deserialize($serialized));
        }
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_serializing_of_resource_throws_exception()
    {
        $serializer = $this->newSerializer();
        $res = opendir(sys_get_temp_dir());
        $serializer->serialize($res);
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_serializing_of_false_throws_exception()
    {
        $serializer = $this->newSerializer();
        $serializer->serialize(false);
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_deserializing_of_malformed_string_throws_exception()
    {
        $serializer = $this->newSerializer();
        $serializer->deserialize('foo');
    }

    protected function newSerializer()
    {
        return new Serializer();
    }
}

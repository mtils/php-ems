<?php

namespace Ems\Core;


use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Testing\Cheat;

class SerializerTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(SerializerContract::class, $this->newSerializer());
    }

    public function test_mimeType_returns_string()
    {
        $mimeType = $this->newSerializer()->mimeType();
        $this->assertInternalType('string', $mimeType);
        $this->assertContains('/', $mimeType);
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
    public function test_serializing_of_special_false_string_throws_exception()
    {
        $serializer = $this->newSerializer();
        $res = opendir(sys_get_temp_dir());
        $falseString = Cheat::get($serializer, 'serializeFalseAs');
        $serializer->serialize($falseString);
    }

    public function test_serializing_of_false_throws_no_exception()
    {
        $serializer = $this->newSerializer();
        $this->assertSame(false, $serializer->deserialize($serializer->serialize(false)));
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_deserializing_of_malformed_string_throws_exception_without_error()
    {
        $hook = function () { return false; };
        $serializer = $this->newSerializer($hook);
        $serializer->deserialize('foo');
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_deserializing_of_malformed_string_throws_exception()
    {
        $serializer = $this->newSerializer();
        $serializer->deserialize('foo');
    }

    public function test_unserializeError_guesses_error()
    {
        $hook = function () { return false; };

        $serializer = $this->newSerializer($hook);
        $this->assertFalse(Cheat::call($serializer, 'unserializeError'));

        $hook = function () {
            return [
                'file'    => 'Some not existing path',
                'message' => 'unserialize(): error'
            ];
        };

        $serializer = $this->newSerializer($hook);

        $this->assertFalse(Cheat::call($serializer, 'unserializeError'));

        $hook = function () {
            return [
                'file'    => realpath('src/Ems/Core/Serializer.php'),
                'message' => 'session_start(): error'
            ];
        };

        $serializer = $this->newSerializer($hook);

        $this->assertFalse(Cheat::call($serializer, 'unserializeError'));
    }

    protected function newSerializer(callable $errorGetter=null)
    {
        return new Serializer($errorGetter);
    }
}

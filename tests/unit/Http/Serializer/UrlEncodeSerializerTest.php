<?php
/**
 *  * Created by mtils on 12.08.19 at 14:21.
 **/

namespace Ems\Http\Serializer;


use Ems\Contracts\Core\Serializer;
use Ems\TestCase;
use stdClass;
use function http_build_query;
use function urlencode;

class UrlEncodeSerializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(Serializer::class, $this->make());
    }

    /**
     * @test
     */
    public function it_returns_correct_mimeType()
    {
        $this->assertEquals('application/x-www-form-urlencoded', $this->make()->mimeType());
    }

    /**
     * @test
     */
    public function it_serializes_strings_correctly()
    {
        $serializer = $this->make();
        $this->assertEquals('hello+you', $serializer->serialize('hello you'));
        $this->assertEquals(urlencode('Grüße zur Märchenstunde Schröder! Kostet 6 €'), $serializer->serialize('Grüße zur Märchenstunde Schröder! Kostet 6 €'));

    }

    /**
     * @test
     */
    public function it_serializes_arrays_correctly()
    {
        $serializer = $this->make();
        $data = [
            'one' => 'partner',
            'two' => 'heads',
            'four' => 'eyes'
        ];

        $serialized = $serializer->serialize($data);

        $this->assertEquals(http_build_query($data), $serialized);
    }

    /**
     * @test
     */
    public function it_serializes_multidimensional_arrays_correctly()
    {
        $serializer = $this->make();
        $data = [
            'one' => 'partner',
            'two' => 'heads',
            'four' => 'eyes',
            'daughter' => [
                'name' => 'caron',
                'age'  => 5,
                'puppet' => [
                    'name' => 'Pipi',
                    'size' => 20
                ]
            ]
        ];

        $serialized = $serializer->serialize($data);

        $this->assertEquals(http_build_query($data), $serialized);
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function it_throws_exception_if_not_stringable()
    {
        $this->make()->deserialize(new stdClass());
    }

    /**
     * @test
     */
    public function it_deserializes_simple_strings()
    {
        $serializer = $this->make();
        $testSentence = 'Grüße zur Märchenstunde Schröder! Kostet 6 €';
        $this->assertEquals('Hello you', $serializer->deserialize('Hello+you'));
        $this->assertEquals($testSentence, $serializer->deserialize(urlencode($testSentence)));
    }

    /**
     * @test
     */
    public function it_deserializes_arrays()
    {
        $serializer = $this->make();
        $data = [
            'one' => 'partner',
            'two' => 'heads',
            'four' => 'eyes'
        ];

        $this->assertEquals($data, $serializer->deserialize($serializer->serialize($data)));

    }

    /**
     * @test
     */
    public function it_deserializes_multidimensional_arrays()
    {
        $serializer = $this->make();
        $data = [
            'one' => 'partner',
            'two' => 'heads',
            'four' => 'eyes',
            'daughter' => [
                'name' => 'caron',
                'age'  => 5,
                'puppet' => [
                    'name' => 'Pipi',
                    'size' => 20
                ]
            ]
        ];

        $this->assertEquals($data, $serializer->deserialize($serializer->serialize($data)));

    }

    /**
     * @return UrlEncodeSerializer
     */
    protected function make()
    {
        return new UrlEncodeSerializer();
    }
}
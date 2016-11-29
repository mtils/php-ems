<?php


namespace Ems\Core;

use Ems\Contracts\Core\Extractor as ExtractorContract;

class ExtractorTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            ExtractorContract::class,
            $this->newExtractor()
        );
    }

    public function test_it_extracts_array_value()
    {
        $extractor = $this->newExtractor();

        $test = [
            'a'     => 'a',
            'foo'   => true,
            'c'     => ['a'=>true]
        ];

        foreach ($test as $key=>$value) {
            $this->assertEquals($value, $extractor->value($test, $key));
        }
    }

    public function test_it_extracts_nested_array_value()
    {
        $extractor = $this->newExtractor();

        $test = [
            'a'     => 'a',
            'foo'   => true,
            'c'     => ['a'=>true],
            'user' => [
                'address' => [
                    'coordinate' => [
                        'latitude' => 4.36
                    ]
                ]
            ]
        ];

        $this->assertEquals(4.36, $extractor->value($test, 'user.address.coordinate.latitude'));
    }

    public function test_it_extracts_object_value()
    {
        $extractor = $this->newExtractor();

        $test = $this->newObject([
            'a'     => 'a',
            'foo'   => true,
            'c'     => ['a'=>true]
        ]);

        foreach ($test as $key=>$value) {
            $this->assertEquals($value, $extractor->value($test, $key));
        }
    }

    public function test_it_extracts_nested_object_value()
    {
        $extractor = $this->newExtractor();

        $test = $this->newObject([
            'a'     => 'a',
            'foo'   => true,
            'c'     => ['a'=>true],
            'user' => [
                'address' => [
                    'coordinate' => [
                        'latitude' => 4.36
                    ]
                ]
            ]
        ]);

        $this->assertEquals(4.36, $extractor->value($test, 'user.address.coordinate.latitude'));
    }

    protected function newExtractor()
    {
        return new Extractor;
    }

    protected function newObject(array $values=[])
    {
        $object = new \stdClass;
        foreach ($values as $key=>$value) {
            if (is_array($value)) {
                $object->$key = $this->newObject($value);
                continue;
            }
            $object->$key = $value;
        }
        return $object;
    }
}

<?php

namespace Ems\Core;

use Ems\Contracts\Core\Extractor as ExtractorContract;
use Ems\Testing\LoggingCallable;

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
            'c'     => ['a'=> true]
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
            'c'     => ['a'=> true],
            'user'  => [
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
            'c'     => ['a'=> true]
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
            'c'     => ['a'=> true],
            'user'  => [
                'address' => [
                    'coordinate' => [
                        'latitude' => 4.36
                    ]
                ]
            ]
        ]);

        $this->assertEquals(4.36, $extractor->value($test, 'user.address.coordinate.latitude'));
    }

    public function test_value_of_scalar_values_return_null()
    {
        $extractor = $this->newExtractor();
        $this->assertNull($extractor->value(13, ''));
        $this->assertNull($extractor->value('z', ''));
    }

    public function test_type_of_passed_value_if_no_path_passed()
    {
        $extractor = $this->newExtractor();

        $this->assertEquals('NULL', $extractor->type(null));
        $this->assertEquals('string', $extractor->type('foo'));
        $this->assertEquals('integer', $extractor->type(33));
        $this->assertEquals('double', $extractor->type(33.1));
        $this->assertEquals('boolean', $extractor->type(true));
        $this->assertEquals('array', $extractor->type([]));
        $this->assertEquals('stdClass', $extractor->type(new \stdClass()));
    }

    public function test_type_of_array_key_returns_type_of_nested_value()
    {
        $extractor = $this->newExtractor();

        $test = [
            'a'     => 'a',
            'foo'   => true,
            'c'     => ['a'=> true],
            'user'  => [
                'address' => [
                    'coordinate' => [
                        'latitude' => 4.36
                    ]
                ]
            ]
        ];

        $this->assertEquals('double', $extractor->type($test, 'user.address.coordinate.latitude'));
    }

    public function test_type_of_unnested_forwards_to_manual_type_handler()
    {
        $extractor = $this->newExtractor();
        $typeGetter = new LoggingCallable(function ($object, $key) {

            if (!property_exists($object, $key)) {
                return;
            }

            if (!is_object($object->$key)) {
                return gettype($object->$key);
            }

            return get_class($object->$key);

        });

        $extractor->extend('property', $typeGetter);

        $this->assertEquals(NestedSubTypeTest::class, $extractor->type(NestedTypeTest::class, 'subType'));

        $this->assertCount(1, $typeGetter);
    }

    public function test_type_of_nested_forwards_unnested_segments_to_manual_type_handler()
    {
        $extractor = $this->newExtractor();
        $typeGetter = new LoggingCallable(function ($object, $key) {

            if (!property_exists($object, $key)) {
                return;
            }

            if (!is_object($object->$key)) {
                return gettype($object->$key);
            }

            return get_class($object->$key);

        });

        $extractor->extend('property', $typeGetter);

        $this->assertEquals(NestedChildTypeTest::class, $extractor->type(NestedTypeTest::class, 'subType.childType'));

        $this->assertCount(2, $typeGetter);
        $this->assertInstanceOf(NestedTypeTest::class, $typeGetter->args(0)[0]);
        $this->assertEquals('subType', $typeGetter->args(0)[1]);
        $this->assertInstanceOf(NestedSubTypeTest::class, $typeGetter->args(1)[0]);
        $this->assertEquals('childType', $typeGetter->args(1)[1]);
    }

    public function test_type_stores_results_in_cache()
    {
        $extractor = $this->newExtractor();
        $typeGetter = new LoggingCallable(function ($object, $key) {

            if (!property_exists($object, $key)) {
                return;
            }

            if (!is_object($object->$key)) {
                return gettype($object->$key);
            }

            return get_class($object->$key);

        });

        $extractor->extend('property', $typeGetter);

        $this->assertEquals(NestedChildTypeTest::class, $extractor->type(NestedTypeTest::class, 'subType.childType'));

        $this->assertCount(2, $typeGetter);

        $this->assertEquals(NestedChildTypeTest::class, $extractor->type(NestedTypeTest::class, 'subType.childType'));

        $this->assertCount(2, $typeGetter);
    }

    protected function newExtractor()
    {
        return new Extractor();
    }

    protected function newObject(array $values=[])
    {
        $object = new \stdClass();
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

class NestedTypeTest
{
    public $subType;
    public function __construct()
    {
        $this->subType = new NestedSubTypeTest();
    }
}

class NestedSubTypeTest
{
    public $childType;
    public function __construct()
    {
        $this->childType = new NestedChildTypeTest();
    }
}

class NestedChildTypeTest
{
}

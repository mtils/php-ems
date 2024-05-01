<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 19.11.17
 * Time: 08:27
 */

namespace Ems\Core;


use Ems\Contracts\Core\Provider;
use Ems\Core\Exceptions\KeyLengthException;
use Ems\Core\Serializer\JsonSerializer;
use Ems\Core\Storages\FileStorage;
use Ems\Core\Storages\NestedFileStorage;
use Ems\Core\Support\BootingArrayData;
use Ems\TestCase;
use Ems\Testing\FilesystemMethods;
use Ems\Contracts\Core\Serializer as SerializerContract;

class ArrayProviderTest extends TestCase
{
    use FilesystemMethods;

    protected $shouldPurgeTempFiles = false;

    public function test_implements_interfaces()
    {
        $this->assertInstanceOf(\ArrayAccess::class, $this->newProvider());
        $this->assertInstanceOf(Provider::class, $this->newProvider());
    }

    public function test_add()
    {
        $data = [];
        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($data));

        $this->assertSame($provider, $provider->add($data, 'package'));
    }

    public function test_prepend()
    {
        $data = [];
        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->prepend($data));

        $this->assertSame($provider, $provider->prepend($data, 'package'));
    }

    public function test_get_from_single_added_data()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($data));

        $this->assertEquals('b', $provider->get('a'));
        $this->assertEquals('d', $provider->get('c'));
        $this->assertEquals('g', $provider->get('e.f'));
        $this->assertEquals(['k', 'l'], $provider->get('e.j'));
        $this->assertNull($provider->get('z'));
        $this->assertNull($provider->get('e.m'));

    }

    public function test_getOrFail_from_single_added_data()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($data));

        $this->assertEquals('b', $provider->getOrFail('a'));
        $this->assertEquals('d', $provider->getOrFail('c'));
        $this->assertEquals('g', $provider->getOrFail('e.f'));
        $this->assertEquals(['k', 'l'], $provider->getOrFail('e.j'));

    }

    public function test_getOrFail_throws_exception_if_key_not_found()
    {
        $this->expectException(
            \Ems\Core\Exceptions\KeyNotFoundException::class
        );
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($data));

        $provider->getOrFail('e.m');

    }

    public function test_offsetGet_from_single_added_data()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($data));

        $this->assertEquals('b', $provider->offsetGet('a'));
        $this->assertEquals('d', $provider->offsetGet('c'));
        $this->assertEquals('g', $provider->offsetGet('e.f'));
        $this->assertEquals(['k', 'l'], $provider->offsetGet('e.j'));

    }

    public function test_offsetExists_from_single_added_data()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($data));

        $this->assertTrue($provider->offsetExists('a'));
        $this->assertTrue($provider->offsetExists('c'));
        $this->assertTrue($provider->offsetExists('e.f'));
        $this->assertTrue($provider->offsetExists('e.j'));
        $this->assertFalse($provider->offsetExists('z'));
        $this->assertFalse($provider->offsetExists('e.m'));

    }

    public function test_offsetExists_from_not_added_namespace_throws_no_exception()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($data));

        $this->assertFalse($provider->offsetExists('ns::z'));
    }

    public function test_get_from_single_added_ArrayAccess_data()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $dataObj = new \ArrayObject($data);

        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($dataObj));

        $this->assertEquals('b', $provider->get('a'));
        $this->assertEquals('d', $provider->get('c'));
        $this->assertEquals('g', $provider->get('e.f'));
        $this->assertEquals(['k', 'l'], $provider->get('e.j'));
        $this->assertNull($provider->get('z'));
        $this->assertNull($provider->get('e.m'));

    }

    public function test_get_from_multiple_added_data()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $data2 = [
            'e' => [
                'f' => 'y',
                'j' => ['o', 'o']
            ],
            'o' => ['i'],
            'x' => 'y'
        ];

        $provider = $this->newProvider();

        $provider->add($data2)->add($data);


        $this->assertEquals('b', $provider->get('a'));
        $this->assertEquals('d', $provider->get('c'));
        $this->assertEquals('y', $provider->get('e.f'));
        $this->assertEquals(['o', 'o'], $provider->get('e.j'));
        $this->assertEquals(['i'], $provider->get('o'));
        $this->assertEquals('y', $provider->get('x'));
        $this->assertNull($provider->get('z'));
        $this->assertNull($provider->get('e.m'));

    }

    public function test_get_from_multiple_prepended_data()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $data2 = [
            'e' => [
                'f' => 'y',
                'j' => ['o', 'o']
            ],
            'o' => ['i'],
            'x' => 'y'
        ];

        $provider = $this->newProvider();

        $this->assertSame($provider, $provider->add($data)->prepend($data2));


        $this->assertEquals('b', $provider->get('a'));
        $this->assertEquals('d', $provider->get('c'));
        $this->assertEquals('y', $provider->get('e.f'));
        $this->assertEquals(['o', 'o'], $provider->get('e.j'));
        $this->assertEquals(['i'], $provider->get('o'));
        $this->assertEquals('y', $provider->get('x'));
        $this->assertNull($provider->get('z'));
        $this->assertNull($provider->get('e.m'));

    }

    public function test_get_from_single_added_data_in_different_namespace()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($data, 'package'));

        $this->assertEquals('b', $provider->get('package::a'));
        $this->assertEquals('d', $provider->get('package::c'));
        $this->assertEquals('g', $provider->get('package::e.f'));
        $this->assertEquals(['k', 'l'], $provider->get('package::e.j'));
        $this->assertNull($provider->get('package::z'));
        $this->assertNull($provider->get('package::e.m'));

        $this->assertNull($provider->get('a'));
        $this->assertNull($provider->get('c'));
        $this->assertNull($provider->get('e.f'));
        $this->assertNull($provider->get('e.j'));

    }

    public function test_get_different_data_from_single_added_data_in_different_namespaces()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $provider = $this->newProvider();
        $provider->add($data['e']);
        $provider->add($data, 'package');



        $this->assertEquals('b', $provider->get('package::a'));
        $this->assertEquals('d', $provider->get('package::c'));
        $this->assertEquals('g', $provider->get('package::e.f'));
        $this->assertEquals(['k', 'l'], $provider->get('package::e.j'));
        $this->assertNull($provider->get('package::z'));
        $this->assertNull($provider->get('package::e.m'));

        $this->assertEquals('g', $provider->get('f'));
        $this->assertEquals('i', $provider->get('h'));
        $this->assertEquals(['k', 'l'], $provider->get('j'));
        $this->assertNull($provider->get('package::f'));

    }

    public function test_get_uses_queryCache()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $data2 = [
            'e' => [
                'f' => 'y',
                'j' => ['o', 'o']
            ],
            'o' => ['i'],
            'x' => 'y'
        ];

        $dataObj = new ArrayProviderTest_Array($data);
        $dataObj2 = new ArrayProviderTest_Array($data2);

        $provider = $this->newProvider();

        $this->assertSame($provider, $provider->add($dataObj)->prepend($dataObj2));


        $this->assertEquals('b', $provider->get('a'));
        $this->assertEquals('d', $provider->get('c'));
        $this->assertEquals('y', $provider->get('e.f'));
        $this->assertEquals(['o', 'o'], $provider->get('e.j'));
        $this->assertEquals(['i'], $provider->get('o'));
        $this->assertEquals('y', $provider->get('x'));
        $this->assertNull($provider->get('z'));
        $this->assertNull($provider->get('e.m'));

        $dataObj->throwOnAccess = true;
        $dataObj2->throwOnAccess = true;
        $this->assertEquals('b', $provider->get('a'));
        $this->assertEquals(['o', 'o'], $provider->get('e.j'));
        $this->assertNull($provider->get('z'));
    }

    public function test_get_from_added_data_with_min_keyLength_2()
    {
        $data = [
            'en.a' => 'b',
            'en.c' => 'd',
            'en.e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'de.m' => 'n',
            'de.o' => ['p', 'q', 'r']
        ];

        $dataObj = new ArrayProviderTest_Array($data);
        $dataObj->minKeyLength = 2;

        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($dataObj));

        $this->assertEquals('b', $provider->get('en.a'));
        $this->assertEquals('d', $provider->get('en.c'));
        $this->assertEquals('g', $provider->get('en.e.f'));
        $this->assertEquals(['k', 'l'], $provider->get('en.e.j'));
        $this->assertEquals('n', $provider->get('de.m'));
        $this->assertEquals(['p', 'q', 'r'], $provider->get('de.o'));

        $this->assertNull($provider->get('en.z'));
        $this->assertNull($provider->get('e.m'));

    }

    public function test_get_with_FileStorage()
    {

        $url = new Url($this->tempDir());
        $storage = $this->newStorage()->setUrl($url);

        $data = [
            'app' => [
                'name'     => 'EMS Application',
                'url'      => 'http://localhost',
                'timezone' => 'UTZ'
            ],
            'auth' => [
                'driver' => 'permit',
                'methods' => [
                    'api' => 'basic',
                    'web' => 'session'
                ]
            ],
            'cache' => [
                'stores' => [
                    'default' => [
                        'driver' => 'file',
                        'url'    => '/var/cache/ems-application'
                    ],
                    'fast' => [
                        'driver' => 'file',
                        'url'    => '/dev/shm/ems-application'
                    ],
                    'big' => [
                        'driver' => 'file',
                        'url'    => '/var/tmp/ems-application'
                    ],
                    'jpg' => [
                        'driver' => 'sql',
                        'url'    => '/var/tmp/ems-application'
                    ]
                ],
            ],
        ];

        foreach ($data as $key=>$value) {
            $storage[$key] = $value;
        }

        // Lets do the normal case were the storage did not store the data
        // immediately before loading it
        unset($storage);

        $storage2 = $this->newStorage()->setUrl($url);

        $provider = $this->newProvider();
        $provider->add($storage2);

        $this->assertEquals($data['app']['name'], $provider['app.name']);
        $this->assertEquals($data['app'], $provider['app']);
        $this->assertEquals('file', $provider['cache.stores.default.driver']);


    }

    public function test_get_with_NestedFileStorage()
    {

        $url = new Url($this->tempDir());
        $storage = $this->newNestedStorage()->setUrl($url);

        $data = [
            'de.validation' => [
                'accepted'         => ':attribute muss akzeptiert werden.',
                'active_url'       => ':attribute ist keine gültige Internet-Adresse.',
                'after'            => ':attribute muss ein Datum nach dem :date sein.',
                'alpha'            => ':attribute darf nur aus Buchstaben bestehen.',
                'alpha_dash'       => ':attribute darf nur aus Buchstaben, Zahlen, Binde- und Unterstrichen bestehen. Umlaute (ä, ö, ü) und Eszett (ß) sind nicht erlaubt.',
                'alpha_num'        => ':attribute darf nur aus Buchstaben und Zahlen bestehen.',
                'array'            => ':attribute muss ein Array sein.',
                'before'           => ':attribute muss ein Datum vor dem :date sein.',
                'between'          => [
                    'numeric' => ':attribute muss zwischen :min & :max liegen.',
                    'file'    => ':attribute muss zwischen :min & :max Kilobytes groß sein.',
                    'string'  => ':attribute muss zwischen :min & :max Zeichen lang sein.',
                    'array'   => ':attribute muss zwischen :min & :max Elemente haben.'
                ]
            ],
            'en.validation' => [
                'accepted'             => 'The :attribute must be accepted.',
                'active_url'           => 'The :attribute is not a valid URL.',
                'after'                => 'The :attribute must be a date after :date.',
                'alpha'                => 'The :attribute may only contain letters.',
                'alpha_dash'           => 'The :attribute may only contain letters, numbers, and dashes.',
                'alpha_num'            => 'The :attribute may only contain letters and numbers.',
                'array'                => 'The :attribute must be an array.',
                'before'               => 'The :attribute must be a date before :date.',
                'between'              => [
                    'numeric' => 'The :attribute must be between :min and :max.',
                    'file'    => 'The :attribute must be between :min and :max kilobytes.',
                    'string'  => 'The :attribute must be between :min and :max characters.',
                    'array'   => 'The :attribute must have between :min and :max items.',
                ]
            ]

        ];

        foreach ($data as $key=>$value) {
            $storage[$key] = $value;
        }

        $storage->persist();

        // Lets do the normal case were the storage did not store the data
        // immediately before loading it
        unset($storage);

        $storage2 = $this->newNestedStorage()->setUrl($url);

        $provider = $this->newProvider();
        $provider->add($storage2);

        $this->assertEquals($data['de.validation']['accepted'], $provider['de.validation.accepted']);
        $this->assertEquals($data['de.validation'], $provider['de.validation']);
        $this->assertEquals($data['en.validation']['between']['numeric'], $provider['en.validation.between.numeric']);


    }

    public function test_offsetSet_with_single_added_data()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $provider = $this->newProvider();
        $this->assertSame($provider, $provider->add($data));

        $this->assertEquals('b', $provider->get('a'));
        $this->assertEquals('d', $provider->get('c'));
        $this->assertEquals('g', $provider->get('e.f'));
        $this->assertEquals(['k', 'l'], $provider->get('e.j'));
        $this->assertNull($provider->get('z'));
        $this->assertNull($provider->get('e.m'));

        $provider['a'] = 'c';
        $provider['e.f'] = 'y';
        $provider['e.j'] = ['a', 'b'];
        $provider['z'] = 'a';
        $provider['e.m'] = 'hihi';
        $provider['f'] = [
            'a' => 'b',
            'b' => ['e', 'f']
        ];

        $this->assertEquals('c', $provider['a']);
        $this->assertEquals('y', $provider['e.f']);
        $this->assertEquals(['a', 'b'], $provider['e.j']);
        $this->assertEquals('a', $provider['z']);
        $this->assertEquals('hihi', $provider['e.m']);
        // Lets check the old values does still exist
        $this->assertEquals('i', $provider['e.h']);
        $this->assertEquals('b', $provider['f.a']);
        $this->assertEquals(['e', 'f'], $provider['f.b']);


    }

    public function test_clear_clears_data_in_multiple_namespaces()
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => 'i',
                'j' => ['k', 'l']
            ],
            'm' => 'n',
            'o' => ['p', 'q', 'r']
        ];

        $provider = $this->newProvider();
        $provider->add($data['e']);
        $provider->add($data, 'package');


        $this->assertEquals('b', $provider->get('package::a'));
        $this->assertEquals('d', $provider->get('package::c'));
        $this->assertEquals('g', $provider->get('package::e.f'));
        $this->assertEquals(['k', 'l'], $provider->get('package::e.j'));
        $this->assertNull($provider->get('package::z'));
        $this->assertNull($provider->get('package::e.m'));

        $this->assertEquals('g', $provider->get('f'));
        $this->assertEquals('i', $provider->get('h'));
        $this->assertEquals(['k', 'l'], $provider->get('j'));
        $this->assertNull($provider->get('package::f'));

        $provider->clear('package');

        $this->assertNull($provider->get('package::a'));
        $this->assertNull($provider->get('package::c'));
        $this->assertNull($provider->get('package::e.f'));
        $this->assertNull($provider->get('package::e.j'));

        $this->assertEquals('g', $provider->get('f'));
        $this->assertEquals('i', $provider->get('h'));
        $this->assertEquals(['k', 'l'], $provider->get('j'));
        $this->assertNull($provider->get('package::f'));

        $provider->clear();

        $this->assertNull($provider->get('f'));
        $this->assertNull($provider->get('h'));
        $this->assertNull($provider->get('j'));
    }

    public function test_offsetUnset_throws_exception()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\UnSupported::class);
        $this->newProvider()->offsetUnset('foo');
    }

    public function test_get_from_unknown_namespace_throws_exception()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->newProvider()->get('my-namespace::my-key');
    }

    protected function newProvider()
    {
        return new ArrayProvider();
    }

    protected function newStorage(SerializerContract $serializer=null)
    {
        return new FileStorage($serializer ?: $this->newSerializer());
    }

    protected function newSerializer()
    {
        return (new JsonSerializer())->asArrayByDefault()->prettyByDefault();
    }

    protected function newNestedStorage(SerializerContract $serializer=null)
    {
        return (new NestedFileStorage(null, $serializer ?: $this->newSerializer()))->setNestingLevel(1);
    }
}

class ArrayProviderTest_Array implements \ArrayAccess
{
    use BootingArrayData {
        BootingArrayData::offsetExists as parentOffsetExists;
        BootingArrayData::offsetGet as parentOffsetGet;
    }
    public $throwOnAccess = false;
    public $minKeyLength = 1;

    public function __construct($array)
    {
        $this->_attributes = $array;
        $this->_booted = true;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset) : bool
    {
        if ($this->throwOnAccess) {
            throw new \Exception("Tried to test offset $offset when throwOnGet is true");
        }
        $this->failOnWrongKeyLength($offset);

        return $this->parentOffsetExists($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if ($this->throwOnAccess) {
            throw new \Exception("Tried to get offset $offset when throwOnGet is true");
        }
        $this->failOnWrongKeyLength($offset);
        return $this->parentOffsetGet($offset);
    }

    protected function failOnWrongKeyLength($key)
    {
        if (!$this->keyLengthMatches($key)) {
            throw (new KeyLengthException("Wrong keylength of $key"))->setMinSegments($this->minKeyLength);
        }
    }

    protected function keyLengthMatches($key)
    {
        return count(explode('.', $key)) >= $this->minKeyLength;
    }

}
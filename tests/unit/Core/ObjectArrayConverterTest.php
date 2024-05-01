<?php
/**
 *  * Created by mtils on 13.09.20 at 20:37.
 **/

namespace unit\Core;


use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\ListAdapter;
use Ems\Contracts\Core\ObjectArrayConverter as ObjectArrayConverterContract;
use Ems\Core\Collections\OrderedList;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\ObjectArrayConverter;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Traversable;

use function get_object_vars;
use function is_object;
use function property_exists;

class ObjectArrayConverterTest extends TestCase
{
    #[Test] public function it_implements_interface()
    {
        $this->assertInstanceOf(ObjectArrayConverterContract::class, $this->make());
    }

    #[Test] public function create_simple_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com'
        ];

        $object = $this->make()->fromArray(stdClass::class, $data);
        $this->assertEquals((object)$data, $object);
    }

    #[Test] public function create_simple_nested_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];

        $object = $this->make()->fromArray(stdClass::class, $data);
        $this->assertEquals((object)$data['address'], $object->address);
    }

    #[Test] public function create_simple_custom_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];
        $object = $this->make()->fromArray(ObjectArrayConverterTest_User::class, $data);
        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object);
        $this->assertEquals((object)$data['address'], $object->address);
        $this->assertEquals($data['id'], $object->id);
    }

    #[Test] public function create_simple_custom_nested_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];
        $converter = $this->make();
        $converter->setTypeProvider(function () {
            return ObjectArrayConverterTest_User::class;
        });

        $object = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);
        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object);
        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->address);
        $this->assertEquals($data['address'], get_object_vars($object->address));
//        $this->assertEquals($data['id'], $object->id);
    }

    #[Test] public function create_simple_to_many_nested_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ],
            'projects'  => [
                [
                    'id'    =>  14,
                    'name'  =>  'Garden cleanup'
                ],
                [
                    'id'    =>  17,
                    'name'  =>  'Repair car'
                ],
            ]
        ];

        $converter = $this->make();
        $converter->setTypeProvider($this->makeTypeProvider());

        $object = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object);
        $this->assertInstanceOf(ObjectArrayConverterTest_Address::class, $object->address);
        $this->assertEquals($data['address'], get_object_vars($object->address));

        $this->assertCount(2, $object->projects);

        $this->assertInstanceOf(ObjectArrayConverterTest_Project::class, $object->projects[0]);
        $this->assertInstanceOf(ObjectArrayConverterTest_Project::class, $object->projects[1]);

        $this->assertEquals($data['projects'][0], get_object_vars($object->projects[0]));
        $this->assertEquals($data['projects'][1], get_object_vars($object->projects[1]));
    }

    #[Test] public function create_nested_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City',
                'country'   => [
                    'code'  => 'IT',
                    'name'  => 'Italy'
                ]
            ]
        ];

        $converter = $this->make();
        $converter->setTypeProvider($this->makeTypeProvider());

        $object = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object);
        $this->assertInstanceOf(ObjectArrayConverterTest_Address::class, $object->address);
        $this->assertEquals($data['address']['street'], $object->address->street);
        $this->assertEquals($data['address']['city'], $object->address->city);

        $this->assertInstanceOf(ObjectArrayConverterTest_Country::class, $object->address->country);
        $this->assertEquals($data['address']['country'], get_object_vars($object->address->country));

    }

    #[Test] public function create_to_many_nested_object()
    {
        $data = [
            'id' => 123456,
            'user' => 'login_name',
            'email' => 'michael@outback.com',
            'address' => [
                'street' => 'Elmstreet 5',
                'city' => 'Nightmare City',
                'country'   => [
                    'code'  => 'IT',
                    'name'  => 'Italy'
                ]
            ],
            'projects' => [
                [
                    'id' => 14,
                    'name' => 'Garden cleanup',
                    'address'   => [
                        'street'    =>  'Green Gardens 11',
                        'city'      =>  'Forest'
                    ],
                    'members'   => [
                        [
                            'id'    => 123456,
                            'user'  => 'login_name',
                            'email' =>  'michael@outback.com'
                        ],
                        [
                            'id'    => 123457,
                            'user'  => 'login_name2',
                            'email' =>  'martin@outback.com'
                        ],
                    ]
                ],
                [
                    'id' => 17,
                    'name' => 'Repair car',
                    'address'   => [
                        'street'    =>  'Car repair street',
                        'city'      =>  'Colorado Springs'
                    ],
                    'members'   => [
                        [
                            'id'    => 123456,
                            'user'  => 'login_name',
                            'email' =>  'michael@outback.com'
                        ],
                        [
                            'id'    => 123458,
                            'user'  => 'login_name3',
                            'email' =>  'karin@outback.com'
                        ],
                        [
                            'id'    => 123459,
                            'user'  => 'login_name4',
                            'email' =>  'lilly@outback.com'
                        ],
                    ]
                ],
            ]
        ];

        $converter = $this->make();
        $converter->setTypeProvider($this->makeTypeProvider());

        $object = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object);
        $this->assertInstanceOf(ObjectArrayConverterTest_Address::class, $object->address);
        $this->assertEquals($data['address']['street'], $object->address->street);
        $this->assertEquals($data['address']['city'], $object->address->city);

        $this->assertInstanceOf(ObjectArrayConverterTest_Country::class, $object->address->country);
        $this->assertEquals($data['address']['country'], get_object_vars($object->address->country));

        $this->assertCount(2, $object->projects);

        $this->assertInstanceOf(ObjectArrayConverterTest_Project::class, $object->projects[0]);
        $this->assertInstanceOf(ObjectArrayConverterTest_Project::class, $object->projects[1]);

        $this->assertCount(2, $object->projects[0]->members);
        $this->assertCount(3, $object->projects[1]->members);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[0]->members[0]);
        $this->assertEquals($data['projects'][0]['members'][0], get_object_vars($object->projects[0]->members[0]));

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[0]->members[1]);
        $this->assertEquals($data['projects'][0]['members'][1], get_object_vars($object->projects[0]->members[1]));

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[1]->members[0]);
        $this->assertEquals($data['projects'][1]['members'][0], get_object_vars($object->projects[1]->members[0]));

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[1]->members[1]);
        $this->assertEquals($data['projects'][1]['members'][1], get_object_vars($object->projects[1]->members[1]));

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[1]->members[2]);
        $this->assertEquals($data['projects'][1]['members'][2], get_object_vars($object->projects[1]->members[2]));
    }

    #[Test] public function create_simple_nested_object_by_custom_converter()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];

        $converter = $this->make();

        $converter->addConverter(ObjectArrayConverterTest_User::class, new ObjectArrayConverterTest_SimpleConverter());

        $object = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object);
        $this->assertObjectHasData($data['address'], $object->address);
        $this->assertObjectHasData($data, $object, 'address');

        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->converter);

    }

    #[Test] public function create_nested_object_by_custom_converter()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];

        $converter = $this->make();

        $converter->addConverter(ObjectArrayConverterTest_User::class, new ObjectArrayConverterTest_SimpleConverter());

        $object = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object);
        $this->assertObjectHasData($data['address'], $object->address);
        $this->assertObjectHasData($data, $object, 'address');

        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->converter);

    }

    #[Test] public function create_to_many_nested_object_by_custom_converter()
    {
        $data = [
            'id' => 123456,
            'user' => 'login_name',
            'email' => 'michael@outback.com',
            'address' => [
                'street' => 'Elmstreet 5',
                'city' => 'Nightmare City',
                'country'   => [
                    'code'  => 'IT',
                    'name'  => 'Italy'
                ]
            ],
            'projects' => [
                [
                    'id' => 14,
                    'name' => 'Garden cleanup',
                    'address'   => [
                        'street'    =>  'Green Gardens 11',
                        'city'      =>  'Forest'
                    ],
                    'members'   => [
                        [
                            'id'    => 123456,
                            'user'  => 'login_name',
                            'email' =>  'michael@outback.com'
                        ],
                        [
                            'id'    => 123457,
                            'user'  => 'login_name2',
                            'email' =>  'martin@outback.com'
                        ],
                    ]
                ],
                [
                    'id' => 17,
                    'name' => 'Repair car',
                    'address'   => [
                        'street'    =>  'Car repair street',
                        'city'      =>  'Colorado Springs'
                    ],
                    'members'   => [
                        [
                            'id'    => 123456,
                            'user'  => 'login_name',
                            'email' =>  'michael@outback.com'
                        ],
                        [
                            'id'    => 123458,
                            'user'  => 'login_name3',
                            'email' =>  'karin@outback.com'
                        ],
                        [
                            'id'    => 123459,
                            'user'  => 'login_name4',
                            'email' =>  'lilly@outback.com'
                        ],
                    ]
                ],
            ]
        ];

        $converter = $this->make();
        $converter->setTypeProvider($this->makeTypeProvider());

        $converter->addConverter(ObjectArrayConverterTest_User::class, new ObjectArrayConverterTest_SimpleConverter());
        $converter->addConverter(ObjectArrayConverterTest_Project::class, new ObjectArrayConverterTest_SimpleConverter());
        $converter->addConverter(ObjectArrayConverterTest_Address::class, new ObjectArrayConverterTest_ListConverter());

        $object = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object);

        $this->assertEquals(ObjectArrayConverterTest_ListConverter::class, $object->address->converter);
        $this->assertInstanceOf(ObjectArrayConverterTest_Address::class, $object->address);
        $this->assertEquals($data['address']['street'], $object->address->street);
        $this->assertEquals($data['address']['city'], $object->address->city);

        $this->assertInstanceOf(ObjectArrayConverterTest_Country::class, $object->address->country);
        $this->assertFalse(property_exists($object->address->country, 'converter'));
        $this->assertEquals($data['address']['country'], get_object_vars($object->address->country));

        $this->assertCount(2, $object->projects);

        $this->assertInstanceOf(ObjectArrayConverterTest_Project::class, $object->projects[0]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[0]->converter);
        $this->assertInstanceOf(ObjectArrayConverterTest_Project::class, $object->projects[1]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[1]->converter);

        $this->assertCount(2, $object->projects[0]->members);
        $this->assertCount(3, $object->projects[1]->members);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[0]->members[0]);
        $this->assertObjectHasData($data['projects'][0]['members'][0], $object->projects[0]->members[0]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[0]->members[0]->converter);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[0]->members[1]);
        $this->assertObjectHasData($data['projects'][0]['members'][1], $object->projects[0]->members[1]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[0]->members[1]->converter);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[1]->members[0]);
        $this->assertObjectHasData($data['projects'][1]['members'][0], $object->projects[1]->members[0]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[1]->members[0]->converter);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[1]->members[1]);
        $this->assertObjectHasData($data['projects'][1]['members'][1], $object->projects[1]->members[1]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[1]->members[1]->converter);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[1]->members[2]);
        $this->assertObjectHasData($data['projects'][1]['members'][2], $object->projects[1]->members[2]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[1]->members[2]->converter);
    }

    #[Test] public function create_to_many_nested_object_by_custom_list_converter()
    {
        $data = [
            'id' => 123456,
            'user' => 'login_name',
            'email' => 'michael@outback.com',
            'address' => [
                'street' => 'Elmstreet 5',
                'city' => 'Nightmare City',
                'country'   => [
                    'code'  => 'IT',
                    'name'  => 'Italy'
                ]
            ],
            'projects' => [
                [
                    'id' => 14,
                    'name' => 'Garden cleanup',
                    'address'   => [
                        'street'    =>  'Green Gardens 11',
                        'city'      =>  'Forest'
                    ],
                    'members'   => [
                        [
                            'id'    => 123456,
                            'user'  => 'login_name',
                            'email' =>  'michael@outback.com'
                        ],
                        [
                            'id'    => 123457,
                            'user'  => 'login_name2',
                            'email' =>  'martin@outback.com'
                        ],
                    ]
                ],
                [
                    'id' => 17,
                    'name' => 'Repair car',
                    'address'   => [
                        'street'    =>  'Car repair street',
                        'city'      =>  'Colorado Springs'
                    ],
                    'members'   => [
                        [
                            'id'    => 123456,
                            'user'  => 'login_name',
                            'email' =>  'michael@outback.com'
                        ],
                        [
                            'id'    => 123458,
                            'user'  => 'login_name3',
                            'email' =>  'karin@outback.com'
                        ],
                        [
                            'id'    => 123459,
                            'user'  => 'login_name4',
                            'email' =>  'lilly@outback.com'
                        ],
                    ]
                ],
            ]
        ];

        $converter = $this->make();
        $converter->setTypeProvider($this->makeTypeProvider());

        $converter->addConverter(ObjectArrayConverterTest_User::class, new ObjectArrayConverterTest_SimpleConverter());
        $converter->addConverter(ObjectArrayConverterTest_Project::class, new ObjectArrayConverterTest_ListConverter());
        $converter->addConverter(ObjectArrayConverterTest_Address::class, new ObjectArrayConverterTest_SimpleConverter());

        $object = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object);

        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->address->converter);
        $this->assertInstanceOf(ObjectArrayConverterTest_Address::class, $object->address);
        $this->assertEquals($data['address']['street'], $object->address->street);
        $this->assertEquals($data['address']['city'], $object->address->city);

        $this->assertInstanceOf(ObjectArrayConverterTest_Country::class, $object->address->country);
        $this->assertFalse(property_exists($object->address->country, 'converter'));
        $this->assertEquals($data['address']['country'], get_object_vars($object->address->country));

        $this->assertCount(2, $object->projects);

        $this->assertInstanceOf(ObjectArrayConverterTest_Project::class, $object->projects[0]);
        $this->assertEquals(ObjectArrayConverterTest_ListConverter::class, $object->projects[0]->converter);
        $this->assertInstanceOf(ObjectArrayConverterTest_Project::class, $object->projects[1]);
        $this->assertEquals(ObjectArrayConverterTest_ListConverter::class, $object->projects[1]->converter);

        $this->assertCount(2, $object->projects[0]->members);
        $this->assertInstanceOf(OrderedList::class, $object->projects[0]->members);
        $this->assertCount(3, $object->projects[1]->members);
        $this->assertInstanceOf(OrderedList::class, $object->projects[1]->members);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[0]->members[0]);
        $this->assertObjectHasData($data['projects'][0]['members'][0], $object->projects[0]->members[0]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[0]->members[0]->converter);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[0]->members[1]);
        $this->assertObjectHasData($data['projects'][0]['members'][1], $object->projects[0]->members[1]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[0]->members[1]->converter);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[1]->members[0]);
        $this->assertObjectHasData($data['projects'][1]['members'][0], $object->projects[1]->members[0]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[1]->members[0]->converter);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[1]->members[1]);
        $this->assertObjectHasData($data['projects'][1]['members'][1], $object->projects[1]->members[1]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[1]->members[1]->converter);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object->projects[1]->members[2]);
        $this->assertObjectHasData($data['projects'][1]['members'][2], $object->projects[1]->members[2]);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $object->projects[1]->members[2]->converter);
    }

    #[Test] public function it_fails_if_interface_is_passed_and_no_converter_found()
    {
        $this->expectException(HandlerNotFoundException::class);
        $this->make()->fromArray(Traversable::class, ['id' => 22]);
    }

    #[Test] public function remove_custom_converter()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];

        $converter = $this->make();

        $custom = new ObjectArrayConverterTest_ListConverter();

        $converter->addConverter(ObjectArrayConverterTest_User::class, $custom);

        $object = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object);
        $this->assertObjectHasData($data['address'], $object->address);
        $this->assertObjectHasData($data, $object, 'address');

        $this->assertEquals(ObjectArrayConverterTest_ListConverter::class, $object->converter);

        $converter->removeConverter(ObjectArrayConverterTest_User::class);

        $object2 = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);

        $this->assertInstanceOf(ObjectArrayConverterTest_User::class, $object2);
        $this->assertObjectHasData($data['address'], $object2->address);
        $this->assertObjectHasData($data, $object2, 'address');

        $this->assertFalse(property_exists($object2, 'converter'));

    }

    #[Test] public function newList_manages_array()
    {
        $converter = $this->make();
        $list = $converter->newList(stdClass::class, 'foo');
        $this->assertSame([], $list);
        $item = 'foo';
        $converter->addToList(stdClass::class, 'foo', $list, $item);
        $this->assertSame([$item], $list);

        $item2 = 'bar';
        $converter->addToList(stdClass::class, 'foo', $list, $item2);
        $this->assertSame([$item, $item2], $list);

        $converter->removeFromList(stdClass::class, 'foo', $list, $item2);
        $this->assertSame([$item], $list);
    }

    #[Test] public function newList_manages_by_adapter()
    {
        $class = ObjectArrayConverterTest_User::class;
        $converter = $this->make();
        $converter->addConverter($class, new ObjectArrayConverterTest_ListConverter());
        /** @var OrderedList $list */
        $list = $converter->newList($class, 'foo');
        $this->assertInstanceOf(OrderedList::class, $list);
        $item = 'foo';
        $converter->addToList($class, 'foo', $list, $item);
        $this->assertSame([$item], $list->getSource());

        $item2 = 'bar';
        $converter->addToList($class, 'foo', $list, $item2);
        $this->assertSame([$item, $item2], $list->getSource());

        $converter->removeFromList($class, 'foo', $list, $item2);
        $this->assertSame([$item], $list->getSource());

    }

    #[Test] public function it_throws_exception_if_adding_to_non_array_without_extension()
    {
        $converter = $this->make();
        $list = new OrderedList();
        $item = 'foo';
        $this->expectException(HandlerNotFoundException::class);
        $converter->addToList(stdClass::class, 'foo', $list, $item);
    }

    #[Test] public function it_throws_exception_if_removing_from_non_array_without_extension()
    {
        $converter = $this->make();
        $list = new OrderedList();
        $item = 'foo';
        $this->expectException(HandlerNotFoundException::class);
        $converter->removeFromList(stdClass::class, 'foo', $list, $item);
    }

    #[Test] public function it_gets_and_sets_TypeProvider()
    {
        $converter = $this->make();
        $typeProvider = $this->makeTypeProvider();
        $this->assertSame($converter, $converter->setTypeProvider($typeProvider));
        $this->assertSame($typeProvider, $converter->getTypeProvider());
    }

    #[Test] public function toArray_creates_array()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com'
        ];

        $object = $this->make()->toArray((object)$data);
        $this->assertEquals($data, $object);
    }

    #[Test] public function toArray_of_simple_nested_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];

        $depth0Result = $data;
        $depth0Result['address'] = (object)$data['address'];
        $object = $this->make()->fromArray(stdClass::class, $data);
        $array = $this->make()->toArray($object);
        $this->assertEquals($depth0Result, $array);

        $array2 = $this->make()->toArray($object, 1);
        $this->assertEquals($data, $array2);

    }

    #[Test] public function toArray_with_nested_array()
    {
        $data = [
            'id' => 123456,
            'user' => 'login_name',
            'email' => 'michael@outback.com',
            'address' => [
                'street' => 'Elmstreet 5',
                'city' => 'Nightmare City',
                'country'   => [
                    'code'  => 'IT',
                    'name'  => 'Italy'
                ]
            ],
            'projects' => [
                [
                    'id' => 14,
                    'name' => 'Garden cleanup',
                    'address'   => [
                        'street'    =>  'Green Gardens 11',
                        'city'      =>  'Forest'
                    ],
                    'members'   => [
                        [
                            'id'    => 123456,
                            'user'  => 'login_name',
                            'email' =>  'michael@outback.com'
                        ],
                        [
                            'id'    => 123457,
                            'user'  => 'login_name2',
                            'email' =>  'martin@outback.com'
                        ],
                    ]
                ],
                [
                    'id' => 17,
                    'name' => 'Repair car',
                    'address'   => [
                        'street'    =>  'Car repair street',
                        'city'      =>  'Colorado Springs'
                    ],
                    'members'   => [
                        [
                            'id'    => 123456,
                            'user'  => 'login_name',
                            'email' =>  'michael@outback.com'
                        ],
                        [
                            'id'    => 123458,
                            'user'  => 'login_name3',
                            'email' =>  'karin@outback.com'
                        ],
                        [
                            'id'    => 123459,
                            'user'  => 'login_name4',
                            'email' =>  'lilly@outback.com'
                        ],
                    ]
                ],
            ]
        ];

        $converter = $this->make();

        $object = $converter->fromArray(stdClass::class, $data);

        $object->projects[1]->members = $data['projects'][1]['members'];
        $array = $converter->toArray($object, 5);

        $this->assertEquals($data, $array);

    }

    #[Test] public function toArray_of_simple_nested_object_with_custom_converter()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];

        $depth0Result = $data;
        $depth0Result['address'] = (object)$data['address'];

        $converter = $this->make();
        $converter->addConverter(ObjectArrayConverterTest_User::class, new ObjectArrayConverterTest_SimpleConverter());
        $object = $converter->fromArray(ObjectArrayConverterTest_User::class, $data);
        $array = $converter->toArray($object);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $array['array_converted_by']);
        unset($array['array_converted_by']);
        $this->assertEquals($depth0Result, $array);

        $array2 = $converter->toArray($object, 1);
        $this->assertEquals(ObjectArrayConverterTest_SimpleConverter::class, $array2['array_converted_by']);
        unset($array2['array_converted_by']);
        unset($array2['address']['array_converted_by']);
        $this->assertEquals($data, $array2);

    }

    /**
     * @return ObjectArrayConverter
     */
    protected function make()
    {
        return new ObjectArrayConverter();
    }

    protected function makeTypeProvider()
    {
        return function ($class, $path) {
            if ($class == ObjectArrayConverterTest_User::class) {
                if ($path == 'address') {
                    return ObjectArrayConverterTest_Address::class;
                }
                if ($path == 'projects') {
                    return ObjectArrayConverterTest_Project::class.'[]';
                }
            }
            if ($class == ObjectArrayConverterTest_Address::class) {
                if ($path == 'country') {
                    return ObjectArrayConverterTest_Country::class;
                }
            }
            if ($class == ObjectArrayConverterTest_Project::class) {
                if ($path == 'members') {
                    return ObjectArrayConverterTest_User::class.'[]';
                }
            }
            return ObjectArrayConverterTest_User::class;
        };
    }


}

class ObjectArrayConverterTest_User
{

}

class ObjectArrayConverterTest_Address
{

}

class ObjectArrayConverterTest_Project
{

}

class ObjectArrayConverterTest_Country
{

}

class ObjectArrayConverterTest_SimpleConverter implements ObjectArrayConverterContract
{
    /**
     * Turn an object into an array. If depth
     *
     * @param object $object
     * @param int $depth (default:0)
     * @return array
     */
    public function toArray($object, int $depth = 0) : array
    {
        $array = [];
        foreach (get_object_vars($object) as $property=>$value) {
            if ($property == 'converter') {
                continue;
            }
            if (is_object($value) && $depth > 0) {
                $array[$property] = $this->toArray($value, $depth-1);
                continue;
            }
            $array[$property] = $value;
        }
        $array['array_converted_by'] = static::class;
        return $array;
    }

    /**
     * Create an object of $classOrInterface by the passed array.
     * Mark it as "new" or "from storage" by the third parameter.
     *
     * @param string $classOrInterface
     * @param array $data (optional)
     * @param bool $isFromStorage (default:false)
     *
     * @return object
     */
    public function fromArray(string $classOrInterface, array $data = [], bool $isFromStorage = false)
    {
        $object = new $classOrInterface();
        foreach ($data as $key=>$value) {
            $object->$key = $value;
        }
        $object->converter = static::class;
        return $object;
    }

}

class ObjectArrayConverterTest_ListConverter extends ObjectArrayConverterTest_SimpleConverter implements ListAdapter
{
    /**
     * Create a new list.
     *
     * @param string $classOrInterface
     * @param string $path
     *
     * @return Traversable|array
     */
    public function newList(string $classOrInterface, string $path)
    {
        return new OrderedList();
    }

    /**
     * Add an item to the list.
     *
     * @param string $classOrInterface
     * @param string $path
     * @param Traversable|array $list
     * @param mixed $item
     *
     * @return void
     */
    public function addToList(string $classOrInterface, string $path, &$list, &$item)
    {
        if (!$list instanceof OrderedList) {
            throw new TypeException('I can only work with lists of type ' . OrderedList::class);
        }
        $list->push($item);
    }

    /**
     * Remove an item from the list.
     *
     * @param string $classOrInterface
     * @param string $path
     * @param Traversable|array $list
     * @param mixed $item
     *
     * @return void
     */
    public function removeFromList(string $classOrInterface, string $path, &$list, &$item)
    {
        if (!$list instanceof OrderedList) {
            throw new TypeException('I can only work with lists of type ' . OrderedList::class);
        }
        $list->remove($item);
    }


}
<?php
/**
 *  * Created by mtils on 12.04.2022 at 21:42.
 **/

namespace unit\Core\Iterators;

use ArrayIterator;
use Ems\Core\Iterators\JsonPathIterator;
use Ems\TestCase;
use Iterator;

use function iterator_to_array;

class JsonPathIteratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_acts_as_an_array()
    {
        $this->assertInstanceOf(Iterator::class, $this->make());
    }

    /**
     * @test
     */
    public function it_iterates_simple_array()
    {
        $source = [
            'name' => 'Michael',
            'email' => 'michael@me.org',
            'tags' => ['new','fresh'],
        ];

        $iterator = $this->make($source);

        $awaited = [
            '$.name' => $source['name'],
            '$.email'   => $source['email'],
            '$.tags'    => $source['tags'],
            '$.tags[0]' => $source['tags'][0],
            '$.tags[1]' => $source['tags'][1],
        ];

        $awaitedIterator = new ArrayIterator($awaited);
        $awaitedIterator->rewind();
        $awaitedIterator->valid();
        foreach ($iterator as $key=>$row) {
            $this->assertEquals($awaitedIterator->key(), $key);
            $this->assertEquals($awaitedIterator->current(), $row);
            $awaitedIterator->next();
        }
    }

    /**
     * @test
     */
    public function it_iterates_deeper_array()
    {
        $source = [
            'name' => 'Michael',
            'email' => 'michael@me.org',
            'tags' => ['new','fresh'],
            'projects' => [
                [
                    'name' => 'Cycling',
                    'type' => [
                        'name' => 'Sports'
                    ]
                ],
                [
                    'name' => 'Programming',
                    'type' => [
                        'name' => 'IT'
                    ]
                ],
                [
                    'name' => 'Running',
                    'type' => [
                        'name' => 'Sports'
                    ]
                ]
            ]
        ];
        $awaited = [
            '$.name'                    => $source['name'],
            '$.email'                   => $source['email'],
            '$.tags'                    => $source['tags'],
            '$.tags[0]'                 => $source['tags'][0],
            '$.tags[1]'                 => $source['tags'][1],
            '$.projects'                => $source['projects'],
            '$.projects[0]'             => $source['projects'][0],
            '$.projects[0].name'        => $source['projects'][0]['name'],
            '$.projects[0].type'        => $source['projects'][0]['type'],
            '$.projects[0].type.name'   => $source['projects'][0]['type']['name'],
            '$.projects[1]'             => $source['projects'][1],
            '$.projects[1].name'        => $source['projects'][1]['name'],
            '$.projects[1].type'        => $source['projects'][1]['type'],
            '$.projects[1].type.name'   => $source['projects'][1]['type']['name'],
            '$.projects[2]'             => $source['projects'][2],
            '$.projects[2].name'        => $source['projects'][2]['name'],
            '$.projects[2].type'        => $source['projects'][2]['type'],
            '$.projects[2].type.name'   => $source['projects'][2]['type']['name'],
        ];

        $iterator = $this->make($source);

        $awaitedIterator = new ArrayIterator($awaited);
        $awaitedIterator->rewind();
        $awaitedIterator->valid();
        foreach ($iterator as $key=>$row) {
            $this->assertEquals($awaitedIterator->key(), $key);
            $this->assertEquals($awaitedIterator->current(), $row);
            $awaitedIterator->next();
        }
    }

    /**
     * @test
     */
    public function it_iterates_deeper_array_without_keyPrefix()
    {
        $source = [
            'name' => 'Michael',
            'email' => 'michael@me.org',
            'tags' => ['new','fresh'],
            'projects' => [
                [
                    'name' => 'Cycling',
                    'type' => [
                        'name' => 'Sports'
                    ]
                ],
                [
                    'name' => 'Programming',
                    'type' => [
                        'name' => 'IT'
                    ]
                ],
                [
                    'name' => 'Running',
                    'type' => [
                        'name' => 'Sports'
                    ]
                ]
            ]
        ];
        $awaited = [
            'name'                    => $source['name'],
            'email'                   => $source['email'],
            'tags'                    => $source['tags'],
            'tags[0]'                 => $source['tags'][0],
            'tags[1]'                 => $source['tags'][1],
            'projects'                => $source['projects'],
            'projects[0]'             => $source['projects'][0],
            'projects[0].name'        => $source['projects'][0]['name'],
            'projects[0].type'        => $source['projects'][0]['type'],
            'projects[0].type.name'   => $source['projects'][0]['type']['name'],
            'projects[1]'             => $source['projects'][1],
            'projects[1].name'        => $source['projects'][1]['name'],
            'projects[1].type'        => $source['projects'][1]['type'],
            'projects[1].type.name'   => $source['projects'][1]['type']['name'],
            'projects[2]'             => $source['projects'][2],
            'projects[2].name'        => $source['projects'][2]['name'],
            'projects[2].type'        => $source['projects'][2]['type'],
            'projects[2].type.name'   => $source['projects'][2]['type']['name'],
        ];

        $iterator = $this->make($source)->setKeyPrefix('');
        $this->assertSame('', $iterator->getKeyPrefix());

        $awaitedIterator = new ArrayIterator($awaited);
        $awaitedIterator->rewind();
        $awaitedIterator->valid();
        foreach ($iterator as $key=>$row) {
            $this->assertEquals($awaitedIterator->key(), $key);
            $this->assertEquals($awaitedIterator->current(), $row);
            $awaitedIterator->next();
        }
    }

    /**
     * @test
     */
    public function it_iterates_root_list()
    {
        $source = [
            [
                'name' => 'Rebecca',
                'address' => [
                    'street'    => 'At the end',
                    'city'      =>  'Wakanda'
                ],
                'tags' => ['happy','hungry']
            ],
            [
                'name' => 'Jill',
                'address' => [
                    'street'    => 'At the start',
                    'city'      =>  'Köln'
                ],
                'tags' => ['sad','exhausted']
            ],
            [
                'name' => 'Martin',
                'address' => [
                    'street'    => 'In the east',
                    'city'      =>  'Of nowhere'
                ],
                'tags' => ['crazy','green']
            ]
        ];

        $awaited = [
            '$[0]'                   => $source[0],
            '$[0].name'              => $source[0]['name'],
            '$[0].address'           => $source[0]['address'],
            '$[0].address.street'    => $source[0]['address']['street'],
            '$[0].address.city'      => $source[0]['address']['city'],
            '$[0].tags'              => $source[0]['tags'],
            '$[0].tags[0]'           => $source[0]['tags'][0],
            '$[0].tags[1]'           => $source[0]['tags'][1],
            '$[1]'                   => $source[1],
            '$[1].name'              => $source[1]['name'],
            '$[1].address'           => $source[1]['address'],
            '$[1].address.street'    => $source[1]['address']['street'],
            '$[1].address.city'      => $source[1]['address']['city'],
            '$[1].tags'              => $source[1]['tags'],
            '$[1].tags[0]'           => $source[1]['tags'][0],
            '$[1].tags[1]'           => $source[1]['tags'][1],
            '$[2]'                   => $source[2],
            '$[2].name'              => $source[2]['name'],
            '$[2].address'           => $source[2]['address'],
            '$[2].address.street'    => $source[2]['address']['street'],
            '$[2].address.city'      => $source[2]['address']['city'],
            '$[2].tags'              => $source[2]['tags'],
            '$[2].tags[0]'           => $source[2]['tags'][0],
            '$[2].tags[1]'           => $source[2]['tags'][1],
        ];

        $iterator = $this->make($source);

        $awaitedIterator = new ArrayIterator($awaited);
        $awaitedIterator->rewind();
        $awaitedIterator->valid();
        foreach ($iterator as $key=>$row) {
            $this->assertEquals($awaitedIterator->key(), $key);
            $this->assertEquals($awaitedIterator->current(), $row);
            $awaitedIterator->next();
        }
    }

    /**
     * @test
     */
    public function it_iterates_root_list_without_keyPrefix()
    {
        $source = [
            [
                'name' => 'Rebecca',
                'address' => [
                    'street'    => 'At the end',
                    'city'      =>  'Wakanda'
                ],
                'tags' => ['happy','hungry']
            ],
            [
                'name' => 'Jill',
                'address' => [
                    'street'    => 'At the start',
                    'city'      =>  'Köln'
                ],
                'tags' => ['sad','exhausted']
            ],
            [
                'name' => 'Martin',
                'address' => [
                    'street'    => 'In the east',
                    'city'      =>  'Of nowhere'
                ],
                'tags' => ['crazy','green']
            ]
        ];

        $awaited = [
            '[0]'                   => $source[0],
            '[0].name'              => $source[0]['name'],
            '[0].address'           => $source[0]['address'],
            '[0].address.street'    => $source[0]['address']['street'],
            '[0].address.city'      => $source[0]['address']['city'],
            '[0].tags'              => $source[0]['tags'],
            '[0].tags[0]'           => $source[0]['tags'][0],
            '[0].tags[1]'           => $source[0]['tags'][1],
            '[1]'                   => $source[1],
            '[1].name'              => $source[1]['name'],
            '[1].address'           => $source[1]['address'],
            '[1].address.street'    => $source[1]['address']['street'],
            '[1].address.city'      => $source[1]['address']['city'],
            '[1].tags'              => $source[1]['tags'],
            '[1].tags[0]'           => $source[1]['tags'][0],
            '[1].tags[1]'           => $source[1]['tags'][1],
            '[2]'                   => $source[2],
            '[2].name'              => $source[2]['name'],
            '[2].address'           => $source[2]['address'],
            '[2].address.street'    => $source[2]['address']['street'],
            '[2].address.city'      => $source[2]['address']['city'],
            '[2].tags'              => $source[2]['tags'],
            '[2].tags[0]'           => $source[2]['tags'][0],
            '[2].tags[1]'           => $source[2]['tags'][1],
        ];

        $iterator = $this->make($source)->setKeyPrefix('');

        $awaitedIterator = new ArrayIterator($awaited);
        $awaitedIterator->rewind();
        $awaitedIterator->valid();
        foreach ($iterator as $key=>$row) {
            $this->assertEquals($awaitedIterator->key(), $key);
            $this->assertEquals($awaitedIterator->current(), $row);
            $awaitedIterator->next();
        }
    }

    /**
     * @test
     */
    public function matcher_matches_simple_expressions()
    {
        $this->assertMatches('$.name', '$.name');
        $this->assertDoesNotMatch('$.foo', '$.name');
    }

    /**
     * @test
     */
    public function split_expression()
    {
        $matcher = $this->make();
        $this->assertSame(['name'], $matcher->splitPath('$.name'));
        $this->assertSame(['name'], $matcher->splitPath('name'));
        $this->assertSame(['address','street'], $matcher->splitPath('$.address.street'));
        $this->assertSame(['address','street'], $matcher->splitPath('address.street'));
        $this->assertSame(['addresses', '[2]', 'street'], $matcher->splitPath('addresses[2].street'));
        $this->assertSame(['[2]', 'projects', '[3]', 'tags', '[3]', 'creator', 'name'], $matcher->splitPath('$[2].projects[3].tags[3].creator.name'));
        $this->assertSame(['[2]', 'projects', '[3]', 'tags', '[3]', 'creator', 'name'], $matcher->splitPath('[2].projects[3].tags[3].creator.name'));
    }

    /**
     * @test
     */
    public function matcher_matches_wildcard_keys()
    {
        $this->assertMatches('.*', '.name');
        $this->assertMatches('*', 'name');
        $this->assertMatches('$.address.*', '$.address.street');
        $this->assertDoesNotMatch('$.project.*', '$.address.street');
        $this->assertMatches('$.*.street', '$.address.street');
        $this->assertMatches('*.street', 'address.street');
        $this->assertMatches('[*].project', '[0].project');
        $this->assertDoesNotMatch('[*].project', '[0].projects');
        $this->assertMatches('user.projects[*].tags[*].name', 'user.projects[12].tags[144].name');
        $this->assertDoesNotMatch('user.projects[*].tags[*].name', 'user.projects[12].tags[144].id');
    }

    /**
     * @test
     */
    public function matcher_matches_numeric_indexes()
    {
        $this->assertMatches('projects[12]', 'projects[12]');
        $this->assertDoesNotMatch('projects[12]', 'projects[11]');
    }

    /**
     * @test
     */
    public function matcher_matches_multiple_indexes()
    {
        $this->assertMatches('projects[1,2]', 'projects[1]');
        $this->assertMatches('projects[1,2]', 'projects[2]');
        $this->assertDoesNotMatch('projects[1,2]', 'projects[3]');
        $this->assertDoesNotMatch('projects[1,2]', 'projects[0]');
        $this->assertDoesNotMatch('projects[1,2]', 'projects[12]');

        $this->assertMatches('[*].user.projects[33,34].tags[*].*', '[13].user.projects[33].tags[22].name');
        $this->assertDoesNotMatch('[*].user.projects[33,34].tags[*].*', '[13].user.projects[31].tags[22].name');

        $this->assertDoesNotMatch('[*]', '[13].user');
    }

    /**
     * @test
     */
    public function it_selects_explicit_expressions()
    {
        $source = [
            [
                'name' => 'Rebecca',
                'address' => [
                    'street'    => 'At the end',
                    'city'      =>  'Wakanda'
                ],
                'tags' => ['happy','hungry']
            ],
            [
                'name' => 'Jill',
                'address' => [
                    'street'    => 'At the start',
                    'city'      =>  'Köln'
                ],
                'tags' => ['sad','exhausted']
            ],
            [
                'name' => 'Martin',
                'address' => [
                    'street'    => 'In the east',
                    'city'      =>  'Of nowhere'
                ],
                'tags' => ['crazy','green']
            ]
        ];

        $selector = '$[1]';
        $this->assertResultIs([$selector => $source[1]], $source, $selector);

        $selector = '[1]';
        $this->assertResultIs([$selector => $source[1]], $source, $selector, '');

        $selector = '$[2].address.street';
        $this->assertResultIs([$selector => $source[2]['address']['street']], $source, $selector);

        $selector = '[2].address.street';
        $this->assertResultIs([$selector => $source[2]['address']['street']], $source, $selector, '');

        $selector = 'street';
        $this->assertResultIs([$selector => $source[0]['address']['street']], $source[0]['address'], $selector, '');
    }

    /**
     * @test
     */
    public function it_selects_wildcard_expressions()
    {
        $source = [
            [
                'name' => 'Rebecca',
                'address' => [
                    'street'    => 'At the end',
                    'city'      =>  'Wakanda'
                ],
                'tags' => ['happy','hungry']
            ],
            [
                'name' => 'Jill',
                'address' => [
                    'street'    => 'At the start',
                    'city'      =>  'Köln'
                ],
                'tags' => ['sad','exhausted']
            ],
            [
                'name' => 'Martin',
                'address' => [
                    'street'    => 'In the east',
                    'city'      =>  'Of nowhere'
                ],
                'tags' => ['crazy','green']
            ]
        ];

        $selector = '$[*]';
        $this->assertResultIs([
            '$[0]' => $source[0],
            '$[1]' => $source[1],
            '$[2]' => $source[2]
        ], $source, $selector);

        $selector = '$[*].name';
        $this->assertResultIs([
              '$[0].name' => $source[0]['name'],
              '$[1].name' => $source[1]['name'],
              '$[2].name' => $source[2]['name']
          ], $source, $selector);

        $selector = '$[*].address';
        $this->assertResultIs([
              '$[0].address' => $source[0]['address'],
              '$[1].address' => $source[1]['address'],
              '$[2].address' => $source[2]['address']
          ], $source, $selector);

        $selector = '$[*].address.*';
        $this->assertResultIs([
              '$[0].address.street' => $source[0]['address']['street'],
              '$[0].address.city'   => $source[0]['address']['city'],
              '$[1].address.street' => $source[1]['address']['street'],
              '$[1].address.city'   => $source[1]['address']['city'],
              '$[2].address.street' => $source[2]['address']['street'],
              '$[2].address.city'   => $source[2]['address']['city']
          ], $source, $selector);
    }

    public function assertMatches(string $expression, string $path, $value = '')
    {
        $this->assertTrue($this->expressionMatches($expression, $path, $value), "The path '$path' does not match '$expression'");
    }

    public function assertDoesNotMatch(string $expression, string $path, $value = '')
    {
        $this->assertFalse($this->expressionMatches($expression, $path, $value), "The path '$path' must not match '$expression'");
    }

    public function assertResultIs(array $awaited, array $source, string $selector, $prefix='$')
    {
        $this->assertEquals($awaited, iterator_to_array($this->make($source, $selector)->setKeyPrefix($prefix)));
    }

    protected function expressionMatches(string $expression, string $path, $value)
    {
        $iterator = $this->make([], $expression);
        $pathSplit = $iterator->splitPath($path);
        return $iterator->getMatcher()($path, $pathSplit, $value);
    }

    protected function make(array $source=[], string $selector='') : JsonPathIterator
    {
        return new JsonPathIterator($source, $selector);
    }
}
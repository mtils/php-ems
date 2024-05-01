<?php


namespace Ems\Model\Database;

use Ems\Contracts\Core\Storage;
use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\Database\SQLException;
use Ems\Contracts\Model\QueryableStorage;
use Ems\Contracts\Model\StorageQuery as StorageQueryContract;
use Ems\Core\Url;
use Ems\Expression\Condition;
use Ems\Expression\Constraint;
use Ems\Model\Database\Dialects\SQLiteDialect;


class SQLStorageTest extends \Ems\TestCase
{

    protected $testTable = 'CREATE TABLE `cache_entries` (
        `id`            TEXT UNIQUE,
        `valid_until`   INTEGER,
        `tags`          TEXT,
        `payload`       TEXT,
        PRIMARY KEY(`id`)
    );';

    public function test_implements_interfaces()
    {
        $this->assertInstanceof(
            QueryableStorage::class,
            $this->newStorage()
        );

        $this->assertInstanceof(
            Storage::class,
            $this->newStorage()
        );

    }

    public function test_connection_returns_assigned_connection()
    {
        $con = $this->con();
        $this->assertSame($con, $this->newStorage($con)->connection());
    }

    public function test_storageType_returns_SQL()
    {
        $this->assertEquals(Storage::SQL, $this->newStorage()->storageType());
    }

    public function test_getTable_returns_table()
    {
        $this->assertEquals('foo', $this->newStorage(null, 'foo', null)->getTable());
    }

    public function test_getIdKey_returns_idKey()
    {
        $this->assertEquals('foo', $this->newStorage(null, null, 'foo')->getIdKey());
    }

    public function test_access_without_table_creator_throws_exception()
    {
        $this->expectException(
            SQLNameNotFoundException::class
        );
        $this->newStorage()['foo'];
    }

    public function test_offsetExists_returns_right_value()
    {

        $con = $this->con();

        $storage = $this->newAutoStorage($con);

        $this->assertFalse(isset($storage['foo']));

        $storage['foo'] = [
            'valid_until' => time()+1000,
            'tags'        => '|grandma|grandpa|me|',
            'payload'     => 'Text'
        ];

        $this->assertTrue(isset($storage['foo']));

        $this->assertTrue($storage->wasModified());

    }

    public function test_offsetGet_returns_setted_value()
    {

        $con = $this->con();

        $storage = $this->newAutoStorage($con);

        $this->assertFalse(isset($storage['foo']));

        $data = [
            'valid_until' => 1509310224,
            'tags'        => '|grandma|grandpa|me|',
            'payload'     => 'Text'
        ];

        $storage['foo'] = $data;

        $this->assertTrue(isset($storage['foo']));

        $this->assertTrue($storage->wasModified());

        $this->assertEquals($data, $storage['foo']);

    }

    public function test_store_saves_data()
    {

        $con = $this->con();

        $storage = $this->newAutoStorage($con);

        $this->assertFalse(isset($storage['foo']));

        $data = [
            'valid_until' => '1509310224', // value is casted to string
            'tags'        => '|grandma|grandpa|me|',
            'payload'     => 'Text'
        ];

        $storage['foo'] = $data;

        $data2 = [
            'valid_until' => '1509410224', // value is casted to string
            'tags'        => '|hello|bye|thx|',
            'payload'     => 'Text2'
        ];

        $storage['foo2'] = $data2;

        $this->assertTrue(isset($storage['foo']));
        $this->assertTrue(isset($storage['foo2']));

        $this->assertTrue($storage->wasModified());

        $this->assertEquals($data, $storage['foo']);
        $this->assertEquals($data2, $storage['foo2']);

        $this->assertTrue($storage->persist());

        $this->assertFalse($storage->wasModified());

        $this->assertEquals($data, $storage['foo']);
        $this->assertEquals($data2, $storage['foo2']);

        $storage2 = $this->newAutoStorage($con);

        $this->assertEquals($data, $storage2['foo']);
        $this->assertEquals($data2, $storage2['foo2']);

    }

    public function test_store_saves_and_deletes_data()
    {

        $con = $this->con();

        $storage = $this->newAutoStorage($con);

        $this->assertFalse(isset($storage['foo']));

        $data = [
            'valid_until' => '1509310224', // value is casted to string
            'tags'        => '|grandma|grandpa|me|',
            'payload'     => 'Text'
        ];

        $storage['foo'] = $data;

        $data2 = [
            'valid_until' => '1509410224', // value is casted to string
            'tags'        => '|hello|bye|thx|',
            'payload'     => 'Text2'
        ];

        $storage['foo2'] = $data2;

        $this->assertTrue(isset($storage['foo']));
        $this->assertTrue(isset($storage['foo2']));

        $this->assertTrue($storage->wasModified());

        $this->assertEquals($data, $storage['foo']);
        $this->assertEquals($data2, $storage['foo2']);

        $this->assertTrue($storage->persist());

        $this->assertFalse($storage->wasModified());

        unset($storage['foo']);

        $this->assertTrue($storage->wasModified());

        $this->assertFalse(isset($storage['foo']));
        $this->assertEquals($data2, $storage['foo2']);

        $this->assertTrue($storage->persist());

        $storage2 = $this->newAutoStorage($con);

        $this->assertFalse(isset($storage2['foo']));
        $this->assertEquals($data2, $storage2['foo2']);

    }

    public function test_persist_does_nothing_if_not_modified()
    {

        $con = $this->con();
        $con->onAfter('prepare', function ($query) {
            $this->fail('Storage should not write if not modified');
        });

        $storage = $this->newAutoStorage($con);

        $this->assertFalse($storage->persist());
    }

    public function test_purge_deletes_data()
    {

        $con = $this->con();

        $storage = $this->newAutoStorage($con);

        $this->assertFalse(isset($storage['foo']));

        $data = [
            'valid_until' => '1509310224', // value is casted to string
            'tags'        => '|grandma|grandpa|me|',
            'payload'     => 'Text'
        ];

        $storage['foo'] = $data;

        $data2 = [
            'valid_until' => '1509410224', // value is casted to string
            'tags'        => '|hello|bye|thx|',
            'payload'     => 'Text2'
        ];

        $storage['foo2'] = $data2;

        $this->assertTrue(isset($storage['foo']));
        $this->assertTrue(isset($storage['foo2']));

        $this->assertTrue($storage->wasModified());

        $this->assertEquals($data, $storage['foo']);
        $this->assertEquals($data2, $storage['foo2']);

        $this->assertTrue($storage->persist());

        $this->assertFalse($storage->wasModified());

        $this->assertTrue($storage->purge());

        $this->assertFalse($storage->wasModified());

        $this->assertFalse(isset($storage['foo']));
        $this->assertFalse(isset($storage['foo2']));

        $storage2 = $this->newAutoStorage($con);

        $this->assertFalse(isset($storage2['foo']));
        $this->assertFalse(isset($storage2['foo2']));

    }

    public function test_missing_column_does_not_trigger_table_ceation()
    {
        $this->expectException(
            SQLException::class
        );

        $con = $this->mock(Connection::class);

        $con->shouldReceive('dialect')
            ->andReturn(new SQLiteDialect);

        $e = new SQLNameNotFoundException();
        $con->shouldReceive('select')
            ->andThrow($e);

        $storage = $this->newStorage($con)->createTableBy(function () {
            throw new \Exception('Table creation should not be triggered');
        });

        $data = [
            'valid_until' => '1509310224', // value is casted to string
            'tags'        => '|grandma|grandpa|me|',
            'payload'    => 'Text'
        ];

        $storage['foo'] = $data;

    }

    public function test_where_returns_SQLQuery()
    {

        $con = $this->con();

        $storage = $this->newStorage(false);

        $this->assertInstanceof(StorageQueryContract::class, $storage->where('a', 'b'));

    }

    public function test_where_returns_desired_result()
    {

        $storage = $this->newAutoStorage();

        $this->assertFalse(isset($storage['foo']));

        $rows = [
            'a' => [
                'valid_until' => '1', // value is casted to string
                'tags'        => '|first|a|unequal|all|',
                'payload'     => 'Text-A'
            ],
            'b' => [
                'valid_until' => '2', // value is casted to string
                'tags'        => '|second|b|equal|all|',
                'payload'     => 'Text-B'
            ],
            'c' => [
                'valid_until' => '3', // value is casted to string
                'tags'        => '|third|middle|c|unequal|all|',
                'payload'     => 'Text-C'
            ],
            'd' => [
                'valid_until' => '4', // value is casted to string
                'tags'        => '|forth|d|equal|all|',
                'payload'     => 'Text-D'
            ],
            'e' => [
                'valid_until' => '5', // value is casted to string
                'tags'        => '|last|e|unequal|all|',
                'payload'     => 'Text-E'
            ]
        ];


        foreach ($rows as $id=>$data) {
            $storage[$id] = $data;
        }

        $storage->persist();

        $hit = false;
        foreach ($storage->where('payload', 'Text-E') as $id=>$row) {
            $this->assertEquals('e', $id);
            $this->assertEquals($rows['e'], $row);
            $hit = true;
        }

        $this->assertTrue($hit, 'No query results by the storage');

        $ids = [];
        foreach ($storage->where('payload', 'like', 'Text%') as $id=>$row) {
            $ids[] = $id;
        }
        sort($ids);
        $this->assertEquals(array_keys($rows), $ids);

        $ids = [];
        foreach ($storage->where(new Condition(SQL::key('valid_until'), new Constraint('>', [2], '>') )) as $id=>$row) {
            $ids[] = $id;
        }
        sort($ids);
        $this->assertEquals(['c', 'd', 'e'], $ids);

        $ids = [];
        foreach ($storage->where('tags', 'like', '%|unequal|%')->where('valid_until', ['1', '5']) as $id=>$row) {
            $ids[] = $id;
        }
        sort($ids);
        $this->assertEquals(['a', 'e'], $ids);

    }

    public function test_where_purges_desired_entries()
    {

        $storage = $this->newAutoStorage();

        $this->assertFalse(isset($storage['foo']));

        $rows = [
            'a' => [
                'valid_until' => '1', // value is casted to string
                'tags'        => '|first|a|unequal|all|',
                'payload'     => 'Text-A'
            ],
            'b' => [
                'valid_until' => '2', // value is casted to string
                'tags'        => '|second|b|equal|all|',
                'payload'     => 'Text-B'
            ],
            'c' => [
                'valid_until' => '3', // value is casted to string
                'tags'        => '|third|middle|c|unequal|all|',
                'payload'     => 'Text-C'
            ],
            'd' => [
                'valid_until' => '4', // value is casted to string
                'tags'        => '|forth|d|equal|all|',
                'payload'     => 'Text-D'
            ],
            'e' => [
                'valid_until' => '5', // value is casted to string
                'tags'        => '|last|e|unequal|all|',
                'payload'     => 'Text-E'
            ]
        ];


        foreach ($rows as $id=>$data) {
            $storage[$id] = $data;
        }

        $storage->persist();

        $this->assertTrue(isset($storage['b']));
        $this->assertTrue(isset($storage['d']));

        $this->assertTrue($storage->where('tags', 'like', '%|equal|%')->purge());

        $this->assertFalse(isset($storage['b']));
        $this->assertFalse(isset($storage['d']));


    }

    public function test_where_purge_with_keys()
    {

        $storage = $this->newAutoStorage();

        $this->assertFalse(isset($storage['foo']));

        $rows = [
            'a' => [
                'valid_until' => '1', // value is casted to string
                'tags'        => '|first|a|unequal|all|',
                'payload'     => 'Text-A'
            ],
            'b' => [
                'valid_until' => '2', // value is casted to string
                'tags'        => '|second|b|equal|all|',
                'payload'     => 'Text-B'
            ],
            'c' => [
                'valid_until' => '3', // value is casted to string
                'tags'        => '|third|middle|c|unequal|all|',
                'payload'     => 'Text-C'
            ],
            'd' => [
                'valid_until' => '4', // value is casted to string
                'tags'        => '|forth|d|equal|all|',
                'payload'     => 'Text-D'
            ],
            'e' => [
                'valid_until' => '5', // value is casted to string
                'tags'        => '|last|e|unequal|all|',
                'payload'     => 'Text-E'
            ]
        ];


        foreach ($rows as $id=>$data) {
            $storage[$id] = $data;
        }

        $storage->persist();

        $this->assertTrue(isset($storage['b']));
        $this->assertTrue(isset($storage['d']));

        $this->assertFalse($storage->purge([]));
        $this->assertTrue($storage->purge(['b', 'd']));

        $this->assertFalse(isset($storage['b']));
        $this->assertFalse(isset($storage['d']));


    }


    public function test_keys_returns_all_keys()
    {

        $storage = $this->newAutoStorage();

        $this->assertFalse(isset($storage['foo']));

        $rows = [
            'a' => [
                'valid_until' => '1', // value is casted to string
                'tags'        => '|first|a|unequal|all|',
                'payload'     => 'Text-A'
            ],
            'b' => [
                'valid_until' => '2', // value is casted to string
                'tags'        => '|second|b|equal|all|',
                'payload'     => 'Text-B'
            ],
            'c' => [
                'valid_until' => '3', // value is casted to string
                'tags'        => '|third|middle|c|unequal|all|',
                'payload'     => 'Text-C'
            ],
            'd' => [
                'valid_until' => '4', // value is casted to string
                'tags'        => '|forth|d|equal|all|',
                'payload'     => 'Text-D'
            ],
            'e' => [
                'valid_until' => '5', // value is casted to string
                'tags'        => '|last|e|unequal|all|',
                'payload'     => 'Text-E'
            ]
        ];


        foreach ($rows as $id=>$data) {
            $storage[$id] = $data;
        }

        $keys = $storage->keys()->sort()->getSource();

        $this->assertEquals(array_keys($rows), $keys);

        $storage->persist();

        $storage2 = $this->newAutoStorage($storage->connection());

        $keys = $storage2->keys()->sort()->getSource();

        $this->assertEquals(array_keys($rows), $keys);

        unset($storage2['c']);

        $keys = $storage2->keys()->sort()->getSource();

        $this->assertEquals(['a', 'b', 'd', 'e'], $keys);



    }

    public function test_getIterator_returns_all_data()
    {

        $storage = $this->newAutoStorage();

        $this->assertFalse(isset($storage['foo']));

        $rows = [
            'a' => [
                'valid_until' => '1', // value is casted to string
                'tags'        => '|first|a|unequal|all|',
                'payload'     => 'Text-A'
            ],
            'b' => [
                'valid_until' => '2', // value is casted to string
                'tags'        => '|second|b|equal|all|',
                'payload'     => 'Text-B'
            ],
            'c' => [
                'valid_until' => '3', // value is casted to string
                'tags'        => '|third|middle|c|unequal|all|',
                'payload'     => 'Text-C'
            ],
            'd' => [
                'valid_until' => '4', // value is casted to string
                'tags'        => '|forth|d|equal|all|',
                'payload'     => 'Text-D'
            ],
            'e' => [
                'valid_until' => '5', // value is casted to string
                'tags'        => '|last|e|unequal|all|',
                'payload'     => 'Text-E'
            ]
        ];


        foreach ($rows as $id=>$data) {
            $storage[$id] = $data;
        }

        $tempResult = [];
        foreach ($storage as $id=>$data) {
            $tempResult[$id] = $data;
        }

        $this->assertEquals($rows, $tempResult);

        $storage->persist();

        $storage2 = $this->newAutoStorage($storage->connection());

        $tempResult = [];
        foreach ($storage2 as $id=>$data) {
            $tempResult[$id] = $data;
        }

        $this->assertEquals($rows, $tempResult);

    }

    public function test_toArray_returns_all_data()
    {

        $storage = $this->newAutoStorage();

        $this->assertFalse(isset($storage['foo']));

        $rows = [
            'a' => [
                'valid_until' => '1', // value is casted to string
                'tags'        => '|first|a|unequal|all|',
                'payload'     => 'Text-A'
            ],
            'b' => [
                'valid_until' => '2', // value is casted to string
                'tags'        => '|second|b|equal|all|',
                'payload'     => 'Text-B'
            ],
            'c' => [
                'valid_until' => '3', // value is casted to string
                'tags'        => '|third|middle|c|unequal|all|',
                'payload'     => 'Text-C'
            ],
            'd' => [
                'valid_until' => '4', // value is casted to string
                'tags'        => '|forth|d|equal|all|',
                'payload'     => 'Text-D'
            ],
            'e' => [
                'valid_until' => '5', // value is casted to string
                'tags'        => '|last|e|unequal|all|',
                'payload'     => 'Text-E'
            ]
        ];


        foreach ($rows as $id=>$data) {
            $storage[$id] = $data;
        }

        $this->assertEquals($rows, $storage->toArray());

        $storage->persist();

        $storage2 = $this->newAutoStorage($storage->connection());

        $this->assertEquals($rows, $storage2->toArray());

    }

    protected function newStorage($con = null, $table=null, $idKey='id')
    {
        return new SQLStorage($con ?: $this->con(), $table ?: 'tests', $idKey);
    }

    protected function con(Url $url=null, $dialect=null)
    {
        $url = $url ?: new Url('sqlite://memory');
        $con = new PDOConnection($url);
        if ($dialect !== false) {
            $con->setDialect($dialect ?: new SQLiteDialect);
        }
        return $con;
    }

    protected function newAutoStorage($con = null, $table=null, $idKey='id')
    {
        $storage = $this->newStorage($con, $table ?: 'cache_entries', $idKey);

        return $storage->createTableBy(function (Connection $con, $table, $idKey) {

            $con->write($this->testTable);

        });
    }

    protected function queryPrinter($event=null)
    {
        return function ($query, $bindings) use ($event) {
            echo "\n$event: " . SQL::render($query, $bindings);
            print_r($bindings);
        };
    }

    protected function installPrinter(Connection $con)
    {
        foreach (['select', 'insert', 'write', 'prepare'] as $event) {
            $con->onAfter($event, $this->queryPrinter($event));
        }
    }

}

<?php
/**
 *  * Created by mtils on 09.06.19 at 14:25.
 **/

namespace Ems\Model\Database\Storages;


use Ems\Contracts\Core\Storage;
use Ems\Contracts\Expression\Prepared;
use Ems\Contracts\Model\Database\Connection;
use Ems\Core\Collections\OrderedList;
use Ems\Core\Helper;
use Ems\Core\Serializer\BlobSerializer;
use Ems\Core\Url;
use Ems\Model\Database\Dialects\SQLiteDialect;
use Ems\Model\Database\PDOConnection;
use Ems\Model\Database\SQL;
use Ems\TestCase;
use function array_chunk;
use function print_r;
use function uniqid;

class KeyValueStorageTest extends TestCase
{

    protected $testTable = 'CREATE TABLE `tests_entries` (
        `key`            PRIMARY KEY,
        `resource_name` TEXT,
        `data`       TEXT
    );';

    protected $testTable2 = 'CREATE TABLE `tests_entries` (
        `key`            PRIMARY KEY,
        `namespace` TEXT,
        `resource_name` TEXT,
        `data`       TEXT
    );';

    public function test_implements_interfaces()
    {
        $this->assertInstanceof(
            Storage::class,
            $this->newStorage()
        );

    }

    public function test_storageType_returns_SQL()
    {
        $this->assertEquals(Storage::SQL, $this->newStorage()->storageType());
    }

    public function test_getTable_returns_table()
    {
        $this->assertEquals('foo', $this->newStorage(null, 'foo', null)->getTable());
    }

    public function test_getBlobKey_returns_blobKey()
    {
        $this->assertEquals('foo', $this->newStorage(null, null, 'foo')->getBlobKey());
    }

    public function test_offsetExists_returns_right_value()
    {

        $con = $this->con();

        $data = [
            'some'        => 'stuff',
            'stored'      => 'into',
            'database'    => 88
        ];

        $id=1;

        $storage = $this->newStorage($con);

        $this->assertFalse(isset($storage[$id]));

        $storage[$id] = $data;

        $this->assertTrue(isset($storage[$id]));

    }

    /**
     * @expectedException \Ems\Core\Exceptions\UnConfiguredException
     *
     */
    public function test_offsetExists_throws_exception_if_discriminator_not_set()
    {

        $con = $this->con();

        $storage = $this->newEmptyStorage($con);

        $this->assertFalse(isset($storage[1]));

    }

    public function test_offsetGet_returns_setted_value()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $this->assertFalse(isset($storage[1]));

        $data = [
            'some'        => 'stuff',
            'stored'      => 'into',
            'database'    => 88
        ];

        $id = '{bla-bli}';
        $storage[$id] = $data;

        $this->assertTrue(isset($storage[$id]));

        $this->assertEquals($data, $storage[$id]);

    }

    /**
     * @expectedException \Ems\Core\Exceptions\KeyNotFoundException
     */
    public function test_offsetGet_throws_exception_if_key_not_found()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $storage['not-existing-key'];

    }

    public function test_offsetSet_updates_value()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $this->assertFalse(isset($storage[1]));

        $data = [
            'some'        => 'stuff',
            'stored'      => 'into',
            'database'    => 88
        ];

        $id = 'some-complete-random-id';

        $storage[$id] = $data;

        $this->assertTrue(isset($storage[$id]));

        $this->assertEquals($data, $storage[$id]);

        $data['database'] = 99;

        $storage[$id] = $data;

        $this->assertEquals($data, $storage[$id]);

    }

    /**
     * This was also to see using the same prepared query in code coverage.
     */
    public function test_offsetSet_updates_value_twice()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $this->assertFalse(isset($storage[1]));

        $data = [
            'some'        => 'stuff',
            'stored'      => 'into',
            'database'    => 88
        ];

        $id = 123456789;

        $storage[$id] = $data;

        $this->assertTrue(isset($storage[$id]));

        $this->assertEquals($data, $storage[$id]);

        $data['database'] = 99;

        $storage[$id] = $data;

        $this->assertEquals($data, $storage[$id]);

        $data2 = [
            'some'        => 'ramdom stuff',
            'stored'      => 'into',
            'database'    => 85
        ];

        $storage[$id] = $data2;

        $this->assertEquals($data2, $storage[$id]);

    }

    public function test_offsetUnset_removes_entry()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $this->assertFalse(isset($storage[1]));

        $data = [
            'some'        => 'stuff',
            'stored'      => 'into',
            'database'    => 88
        ];

        $id = uniqid();
        $storage[$id] = $data;

        $this->assertTrue(isset($storage[$id]));

        $this->assertEquals($data, $storage[$id]);

        unset($storage[$id]);

        $this->assertFalse(isset($storage[$id]));

    }

    /**
     * @expectedException \Ems\Core\Exceptions\DataIntegrityException
     */
    public function test_offsetUnset_throws_exception_if_delete_changed_more_than_one_row()
    {

        $con = $this->mock(Connection::class);
        $con->shouldReceive('dialect')->andReturn(
            new SQLiteDialect()
        );



        $con->shouldReceive('write')->once()->andReturn(0); // CREATE TABLE

        $storage = $this->newStorage($con);

        $statement = $this->mock(Prepared::class);
        $statement->shouldReceive('write')->with(['id' => 1], true)->andReturn(2);

        $con->shouldReceive('prepare')->andReturn($statement);

        unset($storage[1]);

    }

    /**
     * @expectedException \Ems\Core\Exceptions\KeyNotFoundException
     */
    public function test_offsetUnset_throws_exception_if_delete_failed()
    {

        $con = $this->mock(Connection::class);
        $con->shouldReceive('dialect')->andReturn(
            new SQLiteDialect()
        );

        $con->shouldReceive('write')->once()->andReturn(0); // CREATE TABLE

        $storage = $this->newStorage($con);

        $statement = $this->mock(Prepared::class);
        $statement->shouldReceive('write')->with(['id' => 1], true)->andReturn(0);

        $con->shouldReceive('prepare')->andReturn($statement);


        unset($storage[1]);

    }

    public function test_purge_removes_all_entries()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $this->assertFalse(isset($storage[1]));

        $entries = [];
        $ids = [];

        for ($i=0;$i<20;$i++) {
            $id = "id#$i";
            $entries[$i] = [
                'foo' => 'foo ' . $i,
                'bar' => $i . '. bar'
            ];
            $ids[] = $id;
            $storage[$id] = $entries[$i];
        }

        foreach ($ids as $id) {
            $this->assertTrue(isset($storage[$id]));
        }

        $this->assertTrue($storage->purge());

        foreach ($ids as $id) {
            $this->assertFalse(isset($storage[$id]));
        }

    }

    public function test_clear_removes_all_entries()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $this->assertFalse(isset($storage[1]));

        $entries = [];
        $ids = [];

        for ($i=0;$i<20;$i++) {
            $id = uniqid();
            $entries[$i] = [
                'foo' => 'foo ' . $i,
                'bar' => $i . '. bar'
            ];
            $ids[] = $id;
            $storage[$id] = $entries[$i];
        }

        foreach ($ids as $id) {
            $this->assertTrue(isset($storage[$id]));
        }

        $this->assertSame($storage, $storage->clear());

        foreach ($ids as $id) {
            $this->assertFalse(isset($storage[$id]));
        }

    }

    public function test_purge_removes_passed_entries()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $this->assertFalse(isset($storage[1]));

        $entries = [];
        $ids = [];

        for ($i=0;$i<20;$i++) {
            $id = "id#$i";
            $entries[$i] = [
                'foo' => 'foo ' . $i,
                'bar' => $i . '. bar'
            ];
            $ids[] = $id;
            $storage[$id] = $entries[$i];
        }


        list($remove, $keep) = array_chunk($ids, 10);

        $this->assertTrue($storage->purge($remove));

        foreach ($remove as $id) {
            $this->assertFalse(isset($storage[$id]));
        }

        foreach ($keep as $id) {
            $this->assertTrue(isset($storage[$id]));
        }

    }

    public function test_purge_with_empty_array_does_nothing()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $entries = [];
        $ids = [];

        for ($i=0;$i<20;$i++) {
            $id = "id#$i";
            $entries[$i] = [
                'foo' => 'foo ' . $i,
                'bar' => $i . '. bar'
            ];
            $ids[] = $id;
            $storage[$id] = $entries[$i];
        }

        foreach ($ids as $id) {
            $this->assertTrue(isset($storage[$id]));
        }

        $this->assertFalse($storage->purge([]));

        foreach ($ids as $id) {
            $this->assertTrue(isset($storage[$id]));
        }

    }

    public function test_clear_with_empty_array_does_nothing()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $entries = [];
        $ids = [];

        for ($i=0;$i<20;$i++) {
            $id = "id#$i";
            $entries[$i] = [
                'foo' => 'foo ' . $i,
                'bar' => $i . '. bar'
            ];
            $ids[] = $id;
            $storage[$id] = $entries[$i];
        }

        foreach ($ids as $id) {
            $this->assertTrue(isset($storage[$id]));
        }

        $this->assertSame($storage, $storage->clear([]));

        foreach ($ids as $id) {
            $this->assertTrue(isset($storage[$id]));
        }

    }

    public function test_keys_returns_all_ids()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $entries = [];
        $ids = [];

        for ($i=0;$i<20;$i++) {
            $id = "id#$i";
            $entries[$i] = [
                'foo' => 'foo ' . $i,
                'bar' => $i . '. bar'
            ];
            $ids[] = $id;
            $storage[$id] = $entries[$i];
        }

        $keys = $storage->keys();
        $this->assertCount(20, $keys);
        $this->assertInstanceOf(OrderedList::class, $keys);

    }

    public function test_toArray_returns_all_data()
    {

        $con = $this->con();

        $storage = $this->newStorage($con);

        $entries = [];
        $ids = [];

        for ($i=0;$i<20;$i++) {
            $id = "id#$i";
            $entries[$id] = [
                'foo' => 'foo ' . $i,
                'bar' => $i . '. bar'
            ];
            $ids[] = $id;
            $storage[$id] = $entries[$id];
        }

        $storedArray = $storage->toArray();
        $this->assertCount(20, $storedArray);

        foreach ($ids as $id) {
            $this->assertEquals($entries[$id], $storedArray[$id]);
        }

    }

    public function test_two_storages_on_one_table_do_not_clash()
    {

        $con = $this->con();
        $con->write($this->testTable);

        $storageA = $this->newEmptyStorage($con)->setDiscriminator('a-entries');
        $storageB = $this->newEmptyStorage($con)->setDiscriminator('b-entries');

        $this->assertFalse(isset($storageA[1]));

        $this->assertFalse(isset($storageB[1]));

        $aEntries = [];
        $aIds = [];

        $bEntries = [];
        $bIds = [];

        for ($i=0;$i<20;$i++) {

            if ($i % 2) {
                $data = [
                    'foo' => 'A: foo ' . $i,
                    'bar' => 'A: ' . $i . '. bar'
                ];
                $id = 'completely-random-' . $i;
                $storageA[$id] = $data;
                $aIds[] = $id;
                $aEntries[$id] = $data;
                continue;
            }

            $data = [
                'foo' => 'B: foo ' . $i,
                'bar' => 'B: ' . $i . '. bar'
            ];

            $id = 'even-more-random-' . $i;

            $storageB[$id] = $data;
            $bIds[] = $id;
            $bEntries[$id] = $data;
        }

        foreach ($aIds as $id) {
            $this->assertTrue(isset($storageA[$id]));
            $this->assertFalse(isset($storageB[$id]));
        }

        foreach ($bIds as $id) {
            $this->assertFalse(isset($storageA[$id]));
            $this->assertTrue(isset($storageB[$id]));
        }

        $this->assertTrue($storageA->purge());

        // Ensure only the a discriminator entries are deleted!
        foreach ($aIds as $id) {
            $this->assertFalse(isset($storageA[$id]));
            $this->assertFalse(isset($storageB[$id]));
        }

        foreach ($bIds as $id) {
            $this->assertFalse(isset($storageA[$id]));
            $this->assertTrue(isset($storageB[$id]));
        }

    }

    public function test_two_storages_on_one_table_do_not_clash_with_multiple_discriminators()
    {

        $con = $this->con();
        $con->write($this->testTable2);

        $storageA = $this->newEmptyStorage($con)->setDiscriminators([
            'namespace'     => 'tenant-a',
            'resource_name' => 'entries',
        ]);
        $storageB = $this->newEmptyStorage($con)->setDiscriminators([
            'namespace'     => 'tenant-b',
            'resource_name' => 'entries',
        ]);

        $this->assertFalse(isset($storageA[1]));

        $this->assertFalse(isset($storageB[1]));

        $aEntries = [];
        $aIds = [];

        $bEntries = [];
        $bIds = [];

        for ($i=0;$i<20;$i++) {

            if ($i % 2) {
                $data = [
                    'foo' => 'A: foo ' . $i,
                    'bar' => 'A: ' . $i . '. bar'
                ];
                $id = 'completely-random-' . $i;
                $storageA[$id] = $data;
                $aIds[] = $id;
                $aEntries[$id] = $data;
                continue;
            }

            $data = [
                'foo' => 'B: foo ' . $i,
                'bar' => 'B: ' . $i . '. bar'
            ];

            $id = 'even-more-random-' . $i;

            $storageB[$id] = $data;
            $bIds[] = $id;
            $bEntries[$id] = $data;
        }

        foreach ($aIds as $id) {
            $this->assertTrue(isset($storageA[$id]));
            $this->assertFalse(isset($storageB[$id]));
        }

        foreach ($bIds as $id) {
            $this->assertFalse(isset($storageA[$id]));
            $this->assertTrue(isset($storageB[$id]));
        }

        $this->assertTrue($storageA->purge());

        // Ensure only the a discriminator entries are deleted!
        foreach ($aIds as $id) {
            $this->assertFalse(isset($storageA[$id]));
            $this->assertFalse(isset($storageB[$id]));
        }

        foreach ($bIds as $id) {
            $this->assertFalse(isset($storageA[$id]));
            $this->assertTrue(isset($storageB[$id]));
        }

    }

    public function test_persist_returns_true()
    {
        $this->assertTrue($this->newStorage()->persist());
    }

    public function test_isBuffered_returns_false()
    {
        $this->assertFalse($this->newStorage()->isBuffered());
    }

    public function test_getIdKey_returns_set_idKey()
    {
        $this->assertEquals('foo' ,$this->newStorage()->setIdKey('foo')->getIdKey());
    }

    public function test_getDiscriminator_returns_set_discriminator()
    {
        $this->assertEquals('items' ,$this->newStorage()->setDiscriminator('items')->getDiscriminator());
    }

    public function test_getDiscriminatorKey_returns_set_discriminatorKey()
    {
        $this->assertEquals('class' ,$this->newStorage()->setDiscriminatorKey('class')->getDiscriminatorKey());
    }

    public function test_getSerializer_and_setSerializer()
    {
        $serializer = new BlobSerializer();
        $this->assertSame($serializer ,$this->newStorage()->setSerializer($serializer)->getSerializer());
    }

    protected function newStorage($con = null, $table=null, $blobKey='data', $serializer=null)
    {
        $con = $con ?: $this->con();
        $storage = new KeyValueStorage($con, $table ?: 'tests_entries', $blobKey);
        $storage->setIdKey('key');
        $con->write($this->testTable);
        $storage->setDiscriminator('sql-blob-storage-test');
        return $storage;
    }

    protected function newMultiDiscriminatorStorage($con = null, $table=null, $blobKey='data', $serializer=null)
    {
        $con = $con ?: $this->con();
        $storage = new KeyValueStorage($con, $table ?: 'tests_entries', $blobKey);
        $storage->setIdKey('key');
        $con->write($this->test2Table);
        $storage->setDiscriminators([
            'namespace' => 'tenant-a',
            'resource_name' => 'sql-blob-storage-test'
        ]);
        return $storage;
    }

    protected function newEmptyStorage($con = null, $table=null, $blobKey='data', $serializer=null)
    {
        $con = $con ?: $this->con();
        $storage = new KeyValueStorage($con, $table ?: 'tests_entries', $blobKey);
        $storage->setIdKey('key');
        return $storage;
    }

    protected function con(Url $url=null, $dialect=null)
    {
        $url = $url ?: new Url('sqlite://memory');
        $con = new PDOConnection($url);
        if ($dialect !== false) {
            $con->setDialect($dialect ?: new SQLiteDialect);
        }
        return $con;
        $con->onAfter('select', function ($query, $bindings) {
            echo "\n$query";
        });
        $con->onAfter('insert', function ($query, $bindings) {
            echo "\n$query";
        });
        $con->onAfter('write', function ($query, $bindings) {
            if (Helper::startsWith($query, 'CREATE')) {
                return;
            };
            echo "\n$query";
        });
        return $con;
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
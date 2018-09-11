<?php


namespace Ems\Cache\Storage;

use Ems\Contracts\Cache\Storage as CacheStorageContract;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Core\Storage as CoreStorageContract;
use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\QueryableStorage;
use Ems\Core\Exceptions\DataIntegrityException;
use Ems\Core\Serializer\BlobSerializer;
use Ems\Core\Storages\FileStorage;
use Ems\Core\Url;
use Ems\Model\Database\Dialects\SQLiteDialect;
use Ems\Model\Database\PDOConnection;
use Ems\Model\Database\SQLStorage;
use Ems\Testing\Cheat;
use Ems\Testing\FilesystemMethods;
use Ems\Testing\LoggingCallable;


class CoreStorageTest extends \Ems\TestCase
{
    use FilesystemMethods;

    protected $testTable = 'CREATE TABLE `cache_entries` (
        `id`            VARCHAR(255) NOT NULL UNIQUE,
        `plain`         BOOLEAN NOT NULL DEFAULT 0,
        `outside`       BOOLEAN NOT NULL DEFAULT 0,
        `tags`          VARCHAR(255),
        `valid_until`   INTEGER NOT NULL DEFAULT 0,
        `payload`       BLOB,
        PRIMARY KEY(`id`)
    );';

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            CacheStorageContract::class, 
            $this->newStorage()
        );
    }

    public function test_has_returns_false_if_entry_not_found()
    {
        $this->assertFalse($this->newStorage()->has('foo'));
    }

    public function test_escape_creates_nicely_formatted_keys()
    {
        $storage = $this->newStorage();
        $this->assertEquals('foo',$storage->escape('foo'));
        $this->assertEquals('foo_bar',$storage->escape('foo.bar'));
        $this->assertTrue(strlen($storage->escape(str_repeat('b', 1000))) < 256);
    }

    public function test_has_returns_true_if_entry_found()
    {
        $storage = $this->newStorage();
        $storage->put('foo', 'bar');
        $this->assertTrue($storage->has('foo'));
    }

    public function test_getEntryTemplate_returns_template()
    {
        $storage = $this->newStorage();
        $this->assertEquals(Cheat::get($storage, 'entryTemplate'), $storage->getEntryTemplate());
    }
    
    public function test_get_returns_null_if_entry_not_found()
    {
        $this->assertNull($this->newStorage()->get('foo'));
    }

    public function test_get_returns_stored_entry()
    {
        $storage = $this->newStorage();
        $storage->put('foo', 'bar');
        $this->assertEquals('bar', $storage->get('foo'));
    }

    /**
     * @expectedException UnexpectedValueException
     **/
    public function test_put_throws_exception_if_unserializable_value_is_passed_and_no_big_storage_assigned()
    {
        $storage = $this->newStorage();
        $storage->put('foo', fopen(__FILE__, 'r'));
    }

    public function test_put_throws_no_exception_if_unserializable_value_is_passed_and_big_storage_assigned()
    {
        $bigStorage = $this->mock(CoreStorageContract::class);
        $storage = $this->newStorage(null, $bigStorage);

        $logger = new LoggingCallable;
        $storage->on('error', $logger);

        $resource = fopen(__FILE__, 'r');

        $bigStorage->shouldReceive('offsetSet')
                   ->with('foo', $resource)
                   ->once();

        $storage->put('foo', $resource);

        $this->assertCount(0, $logger, 'An error occured during writing to big storage.');
    }
    
    public function test_get_does_not_return_exceeded_entry()
    {
        // We need two storages here because the memory cache entries will not
        // be checked for expiration...
        $con = $this->newConnection();

        $mainStorage = $this->newMainStorage($con);


        $storage = $this->newStorage($mainStorage);

        $until = (new \DateTime());
        $storage->put('foo', 'bar', [], $until);

        $storage2 = $this->newTestStorage($mainStorage);

        $storage2->now = time() + 100;

        $this->assertNull($storage2->get('foo'));
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\ConfigurationError
     **/
    public function test_get_throws_exception_if_entry_marked_as_outside_but_no_bigStorage_assigned()
    {
        // We need two storages here because the memory cache entries will not
        // be checked for expiration...
        $con = $this->newConnection();

        $mainStorage = $this->mock(QueryableStorage::class);
        $mainStorage->shouldReceive('isBuffered')->andReturn(false);


        $storage = $this->newStorage($mainStorage);

        $mainStorage->shouldReceive('offsetExists')
                    ->with('foo')
                    ->andReturn(true);

        $mainStorage->shouldReceive('offsetGet')
                    ->with('foo')
                    ->andReturn([
            'payload'     => null,
            'outside'     => 1,
            'plain'       => 0,
            'tags'        => '',
            'valid_until' => 0
        ]);

        $storage->get('foo');


    }
    
    public function test_get_many_does_not_return_exceeded_entries()
    {
        // We need two storages here because the memory cache entries will not
        // be checked for expiration...
        $con = $this->newConnection();

        $mainStorage = $this->newMainStorage($con);


        $storage = $this->newStorage($mainStorage);

        $until = (new \DateTime());
        $storage->put('foo', 'bar', [], $until);
        $storage->put('baz', 'boing', [], $until);

        $storage2 = $this->newTestStorage($mainStorage);

        $storage2->now = time() + 100;

        $this->assertEquals([], $storage2->several(['foo', 'baz']));
    }

    public function test_get_returns_stored_entries()
    {
        $storage = $this->newStorage();
        $storage->put('foo', 'bar');
        $storage->put('baz', 'boing');
        $storage->put('hi', 'bye');
        $storage->put('chili', 'tasty');

        $this->assertEquals([
            'foo'   => 'bar',
            'baz'   => 'boing',
            'chili' => 'tasty'
        ], $storage->several(['foo', 'baz', 'chili']));
    }

    public function test_get_returns_stored_entries_after_loading()
    {
        $con = $this->newConnection();

        $mainStorage = $this->newMainStorage($con);

        $storage = $this->newStorage($mainStorage);
        $storage->put('foo', 'bar');
        $storage->put('baz', 'boing');
        $storage->put('hi', 'bye');
        $storage->put('chili', 'tasty');

        $this->assertEquals([
            'foo'   => 'bar',
            'baz'   => 'boing',
            'chili' => 'tasty'
        ], $storage->several(['foo', 'baz', 'chili']));

        $mainStorage2 = $this->newMainStorage($con);
        $storage2 = $this->newStorage($mainStorage2);

        $this->assertEquals([
            'foo'   => 'bar',
            'baz'   => 'boing',
            'chili' => 'tasty'
        ], $storage2->several(['foo', 'baz', 'chili']));

        $storage->put('rebecca', 'jill');
        $storage->put('age', 55);

        $this->assertEquals('jill', $storage2->get('rebecca'));

        $this->assertEquals(55, $storage2->get('age'));
        $this->assertTrue(is_int($storage2->get('age')));

    }

    public function test_clear_deletes_all_data()
    {
        $con = $this->newConnection();

        $mainStorage = $this->newMainStorage($con);

        $storage = $this->newStorage($mainStorage);
        $storage->put('foo', 'bar');
        $storage->put('baz', 'boing');
        $storage->put('hi', 'bye');
        $storage->put('chili', 'tasty');

        $this->assertEquals([
            'foo'   => 'bar',
            'baz'   => 'boing',
            'chili' => 'tasty'
        ], $storage->several(['foo', 'baz', 'chili']));

        $storage->clear();

        $this->assertEquals([], $storage->several(['foo', 'baz', 'hi', 'chili']));

        $mainStorage2 = $this->newMainStorage($con);
        $storage2 = $this->newStorage($mainStorage2);

        $this->assertEquals([], $storage2->several(['foo', 'baz', 'hi', 'chili']));
    }

    public function test_increment_and_decrement()
    {
        $con = $this->newConnection();

        $mainStorage = $this->newMainStorage($con);

        $storage = $this->newStorage($mainStorage);
        $this->assertEquals(1, $storage->increment('foo'));
        $this->assertEquals(2, $storage->increment('foo'));
        $this->assertEquals(6, $storage->increment('foo', 4));
        $this->assertEquals(5, $storage->decrement('foo'));
        $this->assertEquals(3, $storage->decrement('foo', 2));
        $this->assertEquals(0, $storage->decrement('foo', 3));

    }

    public function test_forget_deletes_passed_key()
    {
        $con = $this->newConnection();

        $mainStorage = $this->newMainStorage($con);

        $storage = $this->newStorage($mainStorage);
        $storage->put('foo', 'bar');
        $storage->put('baz', 'boing');
        $storage->put('hi', 'bye');
        $storage->put('chili', 'tasty');

        $this->assertEquals([
            'foo'   => 'bar',
            'baz'   => 'boing',
            'hi'    => 'bye',
            'chili' => 'tasty',
        ], $storage->several(['foo', 'baz', 'hi', 'chili']));

        $storage->forget('baz');

        $this->assertFalse($storage->has('baz'));

        $this->assertEquals([
            'foo'   => 'bar',
            'hi'    => 'bye',
            'chili' => 'tasty',
        ], $storage->several(['foo', 'baz', 'hi', 'chili']));

        $mainStorage2 = $this->newMainStorage($con);
        $storage2 = $this->newStorage($mainStorage2);

        $this->assertFalse($storage2->has('baz'));
        $this->assertEquals([
            'foo'   => 'bar',
            'hi'    => 'bye',
            'chili' => 'tasty',
        ], $storage2->several(['foo', 'baz', 'hi', 'chili']));

    }

    public function test_prune_deletes_passed_tags()
    {
        $con = $this->newConnection();

        $mainStorage = $this->newMainStorage($con);

        $entries = [
            'emily' => [
                'payload' => 27,
                'tags'    => ['female', 'young', 'has_pet']
            ],
            'peter' => [
                'payload' => 24,
                'tags'    => ['male', 'young', 'married']
            ],
            'austin' => [
                'payload' => 76,
                'tags'    => ['male', 'old', 'has_pet']
            ],
            'monica' => [
                'payload' => 46,
                'tags'    => ['female', 'has_pet']
            ],
            'rebecca' => [
                'payload' => 81,
                'tags'    => ['female', 'old', 'married']
            ]
        ];

        $storage = $this->newStorage($mainStorage);

        foreach ($entries as $key=>$data) {
            $storage->put($key, $data['payload'], $data['tags']);
        }

        $values = [];

        foreach ($entries as $key=>$data) {
            $values[$key] = $data['payload'];
        }

        $this->assertEquals($values, $storage->several(array_keys($entries)));

        $mainStorage2 = $this->newMainStorage($con);
        $storage2 = $this->newStorage($mainStorage2);

        $this->assertEquals($values, $storage2->several(array_keys($entries)));

        $this->assertSame($storage, $storage->prune([])); // does nothing
        $this->assertSame($storage, $storage->prune(['old']));

        unset($values['austin']);
        unset($values['rebecca']);

        $this->assertEquals($values, $storage->several(array_keys($entries)));

        $storage->prune(['male', 'young']);

        unset($values['peter']);
        unset($values['emily']);

        $this->assertEquals($values, $storage->several(array_keys($entries)));

        $mainStorage3 = $this->newMainStorage($con);
        $storage3 = $this->newStorage($mainStorage2);

        $this->assertEquals($values, $storage3->several(array_keys($entries)));
//         $this->assertEquals($values, $storage2->get(array_keys($entries)));


    }

    public function test_putting_big_values()
    {
        $con = $this->newConnection();

        $entries = [
            'emily' => [
                'payload' => 'Emilies profile, which is so long that should land in big storage',
                'tags'    => ['female', 'young', 'has_pet']
            ],
            'peter' => [
                'payload' => 'Peters short profile',
                'tags'    => ['male', 'young', 'married']
            ],
            'austin' => [
                'payload' => 'Austings profile, which is so long that should land in big storage',
                'tags'    => ['male', 'old', 'has_pet']
            ],
            'monica' => [
                'payload' => 'Monicas profile, which is so long that should land in big storage',
                'tags'    => ['female', 'has_pet']
            ],
            'rebecca' => [
                'payload' => 'Rebeccas short profile',
                'tags'    => ['female', 'old', 'married']
            ]
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $mainStorage = $this->newMainStorage($con);
        $bigStorage = $this->newBigStorage()->setUrl($url);
        $storage = $this->newStorage($mainStorage, $bigStorage);

        $this->assertSame($storage, $storage->setMaxMainStorageBytes(30));
        $this->assertEquals(30, $storage->getMaxMainStorageBytes());

        foreach ($entries as $key=>$data) {
            $storage->put($key, $data['payload'], $data['tags']);
        }

        foreach (['emily', 'austin', 'monica'] as $key) {
            $this->assertTrue(isset($bigStorage[$key]));
            $this->assertEquals($entries[$key]['payload'], $storage->get($key));
        }

        foreach (['peter', 'rebecca'] as $key) {
            $this->assertFalse(isset($bigStorage[$key]));
        }
    }

    public function test_forgetting_big_values()
    {
        $con = $this->newConnection();

        $entries = [
            'emily' => [
                'payload' => 'Emilies profile, which is so long that should land in big storage',
                'tags'    => ['female', 'young', 'has_pet']
            ],
            'peter' => [
                'payload' => 'Peters short profile',
                'tags'    => ['male', 'young', 'married']
            ],
            'austin' => [
                'payload' => 'Austings profile, which is so long that should land in big storage',
                'tags'    => ['male', 'old', 'has_pet']
            ],
            'monica' => [
                'payload' => 'Monicas profile, which is so long that should land in big storage',
                'tags'    => ['female', 'has_pet']
            ],
            'rebecca' => [
                'payload' => 'Rebeccas short profile',
                'tags'    => ['female', 'old', 'married']
            ]
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $mainStorage = $this->newMainStorage($con);
        $bigStorage = $this->newBigStorage()->setUrl($url);
        $storage = $this->newStorage($mainStorage, $bigStorage);

        $this->assertSame($storage, $storage->setMaxMainStorageBytes(30));
        $this->assertEquals(30, $storage->getMaxMainStorageBytes());

        foreach ($entries as $key=>$data) {
            $storage->put($key, $data['payload'], $data['tags']);
        }

        foreach (['emily', 'austin', 'monica'] as $key) {
            $this->assertTrue(isset($bigStorage[$key]));
        }
        foreach (['peter', 'rebecca'] as $key) {
            $this->assertFalse(isset($bigStorage[$key]));
        }

        $storage->forget('emily');
        $this->assertFalse($storage->has('emily'));
        $this->assertFalse(isset($mainStorage['emily']));
        $this->assertFalse(isset($bigStorage['emily']));

        $storage->clear();

        $this->assertFalse($storage->has('austin'));
        $this->assertCount(0, $mainStorage->keys());
        $this->assertCount(0, $bigStorage->keys());

    }

    public function test_forgetting_big_values_by_tags()
    {
        $con = $this->newConnection();

        $entries = [
            'emily' => [
                'payload' => 'Emilies profile, which is so long that should land in big storage',
                'tags'    => ['female', 'young', 'has_pet']
            ],
            'peter' => [
                'payload' => 'Peters short profile',
                'tags'    => ['male', 'young', 'married']
            ],
            'austin' => [
                'payload' => 'Austings profile, which is so long that should land in big storage',
                'tags'    => ['male', 'old', 'has_pet']
            ],
            'monica' => [
                'payload' => 'Monicas profile, which is so long that should land in big storage',
                'tags'    => ['female', 'has_pet']
            ],
            'rebecca' => [
                'payload' => 'Rebeccas short profile',
                'tags'    => ['female', 'old', 'married']
            ]
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $mainStorage = $this->newMainStorage($con);
        $bigStorage = $this->newBigStorage()->setUrl($url);
        $storage = $this->newStorage($mainStorage, $bigStorage);

        $this->assertSame($storage, $storage->setMaxMainStorageBytes(30));
        $this->assertEquals(30, $storage->getMaxMainStorageBytes());

        foreach ($entries as $key=>$data) {
            $storage->put($key, $data['payload'], $data['tags']);
        }

        foreach (['emily', 'austin', 'monica'] as $key) {
            $this->assertTrue(isset($bigStorage[$key]));
        }
        foreach (['peter', 'rebecca'] as $key) {
            $this->assertFalse(isset($bigStorage[$key]));
        }

        $storage->prune(['male']);

        foreach (['peter', 'austin'] as $key) {
            $this->assertFalse($storage->has($key));
            $this->assertFalse(isset($mainStorage[$key]));
            $this->assertFalse(isset($bigStorage[$key]));
        }

        foreach (['emily', 'monica', 'rebecca'] as $key) {
            $this->assertTrue($storage->has($key));
            $this->assertTrue(isset($mainStorage[$key]));
        }

        foreach (['emily', 'monica'] as $key) {
            $this->assertTrue(isset($bigStorage[$key]));
        }

    }

    public function test_get_big_value_with_missing_entry_in_bigStorage_triggers_silent_error()
    {
        $con = $this->newConnection();

        $entries = [
            'emily' => [
                'payload' => 'Emilies profile, which is so long that should land in big storage',
                'tags'    => ['female', 'young', 'has_pet']
            ],
            'peter' => [
                'payload' => 'Peters short profile',
                'tags'    => ['male', 'young', 'married']
            ],
            'austin' => [
                'payload' => 'Austings profile, which is so long that should land in big storage',
                'tags'    => ['male', 'old', 'has_pet']
            ],
            'monica' => [
                'payload' => 'Monicas profile, which is so long that should land in big storage',
                'tags'    => ['female', 'has_pet']
            ],
            'rebecca' => [
                'payload' => 'Rebeccas short profile',
                'tags'    => ['female', 'old', 'married']
            ]
        ];

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $mainStorage = $this->newMainStorage($con);
        $bigStorage = $this->newBigStorage()->setUrl($url);
        $storage = $this->newStorage($mainStorage, $bigStorage);

        $this->assertSame($storage, $storage->setMaxMainStorageBytes(30));
        $this->assertEquals(30, $storage->getMaxMainStorageBytes());

        $logger = new LoggingCallable;
        $storage->on('error', $logger);

        foreach ($entries as $key=>$data) {
            $storage->put($key, $data['payload'], $data['tags']);
        }

        foreach (['emily', 'austin', 'monica'] as $key) {
            $this->assertTrue(isset($bigStorage[$key]));
            $this->assertEquals($entries[$key]['payload'], $storage->get($key));
        }

        unset($bigStorage['monica']);

        // TODO: This triggers a false positive, but currently there is no FAST
        // way to determine that
        //$this->assertFalse($storage->has('monica'));
        $this->assertNull($storage->get('monica'));

        $this->assertInstanceOf(DataIntegrityException::class, $logger->arg(0));
    }

    public function test_unsuccessful_write_to_big_storage_triggers_silent_error()
    {
        $con = $this->newConnection();

        $dirName = $this->tempDirName();
        $url = new Url($dirName);

        $mainStorage = $this->newMainStorage($con);
        $bigStorage = $this->mock(CoreStorageContract::class);
        $storage = $this->newStorage($mainStorage, $bigStorage);

        $this->assertSame($storage, $storage->setMaxMainStorageBytes(30));
        $this->assertEquals(30, $storage->getMaxMainStorageBytes());

        $logger = new LoggingCallable;
        $storage->on('error', $logger);

        $key = 'foo';
        $value = 'Some very long data is that here bohoo bohoo';

        $exception = new \Exception;

        $bigStorage->shouldReceive('offsetSet')
                   ->with($key, $value)
                   ->andThrow($exception);

        $storage->put($key, $value);

        $this->assertInstanceOf(DataIntegrityException::class, $logger->arg(0));

        $this->assertSame($exception, $logger->arg(0)->getPrevious());
    }

    protected function newStorage(QueryableStorage $storage=null, CoreStorageContract $bigStorage=null)
    {
        return new CoreStorage($storage ?: $this->newMainStorage(), $bigStorage);
    }

    protected function newTestStorage(QueryableStorage $storage=null, CoreStorageContract $bigStorage=null)
    {
        return new CoreStorageTest_Storage($storage ?: $this->newMainStorage(), $bigStorage);
    }

    protected function newMainStorage(Connection $con=null)
    {
        $con = $con ?: $this->newConnection();

        $storage = new SQLStorage($con ?: $this->con(), 'cache_entries', 'id');

        return $storage->createTableBy(function (Connection $con, $table, $idKey) {

            $con->write($this->testTable);

        });
    }

    protected function newConnection(Url $url=null)
    {
        $url = $url ?: new Url('sqlite://memory');
        $con = new PDOConnection($url);
        $con->setDialect(new SQLiteDialect);

        return $con;

    }

    protected function newBigStorage(SerializerContract $serializer=null)
    {
        $serializer = $serializer ?: (new BlobSerializer)->setMimeType('application/vnd.php.serialized');
        return new FileStorage($serializer);
    }

}

class CoreStorageTest_Storage extends CoreStorage
{
    public $now;

    protected function now()
    {
        return $this->now ? $this->now : time();
    }
}

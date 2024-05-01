<?php
/**
 *  * Created by mtils on 28.05.19 at 11:46.
 **/

namespace Ems\Model\Repositories;


use Ems\Contracts\Core\PushableStorage;
use Ems\Contracts\Core\Repository;
use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Core\NamedObject;
use Ems\Core\Url;
use Ems\Foundation\InputProcessor;
use Ems\IntegrationTest;
use Ems\Model\Database\Dialects\SQLiteDialect;
use Ems\Model\Database\PDOConnection;
use Ems\Model\Database\SQLBlobStorage;
use Ems\Model\OrmObject;
use PHPUnit\Framework\Attributes\Test;

class OrmStorageRepositoryIntegrationTest extends IntegrationTest
{
    protected $testTable = 'CREATE TABLE `tests_entries` (
        `id`            INTEGER PRIMARY KEY,
        `resource_name` TEXT,
        `data`       TEXT
    );';

    #[Test] public function implements_interface()
    {
        $this->assertInstanceOf(Repository::class, $this->newRepository());
    }

    #[Test] public function makeCreatesObject()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->make($attributes);

        $this->assertInstanceOf(OrmStorageRepositoryIntegrationTest_User::class, $hans);
        $this->assertEquals($attributes['name'], $hans->name);
        $this->assertEquals($attributes['age'], $hans->age);
        $this->assertTrue($hans->isNew());
    }

    #[Test] public function fill_fills_OrmObject()
    {

        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->make($attributes);

        // Changes should be happened -> true
        $this->assertTrue($repo->fill($hans, ['name' => 'monika']));

        // Changes should not be happened -> false
        $this->assertFalse($repo->fill($hans, ['name' => 'monika']));
    }

    #[Test] public function fill_throws_exception_when_model_not_OrmObject()
    {
        $this->expectException(
            \Ems\Contracts\Core\Errors\ConstraintFailure::class
        );

        $repo = $this->newRepository();

        $hans = new NamedObject();

        $repo->fill($hans, ['name' => 'monika']);
    }

    #[Test] public function store_stores_object()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($attributes);

        $this->assertInstanceOf(OrmStorageRepositoryIntegrationTest_User::class, $hans);
        $this->assertEquals($attributes['name'], $hans->name);
        $this->assertEquals($attributes['age'], $hans->age);
        $this->assertFalse($hans->isNew());
        $this->assertFalse($hans->wasModified());
        $this->assertEquals(1, $hans->getId());

        $attributes = [
            'name' => 'Maggy',
            'age'  => 42
        ];

        $maggy = $repo->store($attributes);

        $this->assertInstanceOf(OrmStorageRepositoryIntegrationTest_User::class, $maggy);
        $this->assertEquals($attributes['name'], $maggy->name);
        $this->assertEquals($attributes['age'], $maggy->age);
        $this->assertFalse($maggy->isNew());
        $this->assertFalse($maggy->wasModified());
        $this->assertEquals(2, $maggy->getId());

    }

    #[Test] public function store_throws_DataIntegrityException_if_store_did_fail()
    {
        $this->expectException(
            \Ems\Contracts\Core\Errors\DataCorruption::class
        );
        $storage = $this->mock(PushableStorage::class);
        $storage->shouldReceive('isBuffered')->andReturn(true);
        $storage->shouldReceive('offsetPush')->andReturn(1);
        $storage->shouldReceive('persist')->andReturn(false); // this does the magic

        $repo = $this->newRepository($storage);

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $repo->store($attributes);

    }

    #[Test] public function get_returns_stored_objects()
    {
        $repo = $this->newRepository();

        $hansData = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($hansData);

        $maggyData = [
            'name' => 'Maggy',
            'age'  => 42
        ];

        $maggy = $repo->store($maggyData);

        $this->assertEquals($hansData['name'], $repo->get(1)->name);
        $this->assertEquals($maggyData['name'], $repo->get(2)->name);

        $this->assertNull($repo->get(3));

    }

    #[Test] public function update_saves_changes_in_object()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($attributes);

        $this->assertEquals($hans->name, $repo->get(1)->name);


        $this->assertTrue($repo->update($hans, ['name' => 'Gustav']));

        $gustav = $repo->get(1);
        $this->assertEquals($gustav->name, $hans->name);

        $this->assertFalse($repo->update($gustav, ['name' => 'Gustav']));

    }

    #[Test] public function update_does_not_save_if_object_didnt_change()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($attributes);

        $this->assertEquals($attributes['name'], $repo->get(1)->name);


        $this->assertFalse($repo->update($hans, ['name' => 'Hans']));

    }

    #[Test] public function save_does_not_persist_if_object_didnt_change()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($attributes);

        $this->assertFalse($repo->save($hans));

    }

    #[Test] public function delete_deletes_stored_object()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($attributes);

        $this->assertInstanceOf(OrmStorageRepositoryIntegrationTest_User::class, $hans);
        $this->assertEquals($attributes['name'], $hans->name);
        $this->assertEquals($attributes['age'], $hans->age);
        $this->assertFalse($hans->isNew());
        $this->assertFalse($hans->wasModified());
        $this->assertEquals(1, $hans->getId());

        $attributes = [
            'name' => 'Maggy',
            'age'  => 42
        ];

        $maggy = $repo->store($attributes);

        $this->assertInstanceOf(OrmStorageRepositoryIntegrationTest_User::class, $maggy);
        $this->assertEquals($attributes['name'], $maggy->name);
        $this->assertEquals($attributes['age'], $maggy->age);
        $this->assertFalse($maggy->isNew());
        $this->assertFalse($maggy->wasModified());
        $this->assertEquals(2, $maggy->getId());

        $this->assertTrue($repo->delete($hans));

        $this->assertNull($repo->get(1));

    }

    #[Test] public function itemClass_get_and_set_works()
    {
        $this->assertEquals('foo', $this->newRepository()->setItemClass('foo')->getItemClass());
    }

    #[Test] public function lazyLoader_is_passed_to_objects()
    {
        $return = 'a dududu a dadada is what I want to say to you';

        $lazyLoader = function (OrmObject $ormObject, $key) use ($return) {
            return $return;
        };

        $repository = $this->newRepository();
        $repository->setLazyLoader($lazyLoader);

        /** @var OrmStorageRepositoryIntegrationTest_User $obj */
        $obj = $repository->make(['name' => 'willy']);

        $loader = $obj->getLazyLoader();
        $this->assertEquals($return, $loader($obj, 'sing'));

        // Now get it from the repository
        $loader = $repository->getLazyLoader();
        $this->assertEquals($return, $loader($obj, 'sing'));
    }

    /**
     * @param PushableStorage        $storage (optional)
     * @param InputProcessorContract $caster (optional)
     *
     * @return OrmStorageRepository
     */
    protected function newRepository(PushableStorage $storage=null, InputProcessorContract $caster=null)
    {
        $storage = $storage ?: $this->newStorage();
        $caster = $caster ?: new InputProcessor();
        return (new OrmStorageRepository($storage, $caster))->setItemClass(OrmStorageRepositoryIntegrationTest_User::class);
    }

    protected function newStorage($con = null, $table=null, $blobKey='data', $serializer=null)
    {
        $con = $con ?: $this->con();
        $storage = new SQLBlobStorage($con, $table ?: 'tests_entries', $blobKey);
        $con->write($this->testTable);
        $storage->setDiscriminator('sql-blob-storage-test');
        return $storage;
    }

    protected function con(Url $url=null, $dialect=null)
    {
        $url = $url ?: new Url('sqlite://memory');
        $con = new PDOConnection($url);
        if ($dialect !== false) {
            $con->setDialect($dialect ?: new SQLiteDialect());
        }
        return $con;
    }
}

class OrmStorageRepositoryIntegrationTest_User extends OrmObject
{
    //
    public function getLazyLoader()
    {
        return $this->lazyLoader;
    }
}
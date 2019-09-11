<?php
/**
 *  * Created by mtils on 24.03.19 at 11:54.
 **/

namespace Ems\Core\Repositories;


use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\ChangeTracking;
use Ems\Contracts\Core\IdGenerator;
use Ems\Contracts\Core\PushableStorage;
use Ems\Contracts\Core\Repository;
use Ems\Contracts\Core\Storage;
use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Core\GenericEntity;
use Ems\Core\IdGenerator\IncrementingIdGenerator;
use Ems\Core\NamedObject;
use Ems\Core\Storages\ArrayStorage;
use Ems\Core\Storages\PushableProxyStorage;
use Ems\Foundation\InputProcessor;
use Ems\Model\OrmObject;
use Ems\TestCase;


class StorageRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function implements_interface()
    {
        $this->assertInstanceOf(Repository::class, $this->newRepository());
    }

    /**
     * @test
     */
    public function makeCreatesObject()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->make($attributes);
        $hansData = $hans->toArray();
        $this->assertModelInstance($hans);
        $this->assertEquals($attributes['name'], $hansData['name']);
        $this->assertEquals($attributes['age'], $hansData['age']);
        $this->assertTrue($hans->isNew());
        if ($hans instanceof GenericEntity) {
            $this->assertTrue($hans->wasModified());
            $this->assertEquals('data-object', $hans->resourceName());
            $this->assertEquals('id', $hans->getIdKey());
        }
    }

    /**
     * @test
     */
    public function fill_fills_entity()
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

    /**
     * @test
     * @expectedException \Ems\Contracts\Core\Errors\ConstraintFailure
     */
    public function fill_throws_exception_when_model_not_GenericEntity()
    {

        $repo = $this->newRepository();

        $hans = new NamedObject();

        $repo->fill($hans, ['name' => 'monika']);
    }

    /**
     * @test
     */
    public function store_stores_object()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($attributes);
        $hansData = $hans->toArray();
        $this->assertModelInstance($hans);
        $this->assertEquals($attributes['name'], $hansData['name']);
        $this->assertEquals($attributes['age'], $hansData['age']);
        $this->assertFalse($hans->isNew());
        if ($hans instanceof GenericEntity) {
            $this->assertFalse($hans->wasModified());
        }

        $this->assertEquals(1, $hans->getId());

        $attributes = [
            'name' => 'Maggy',
            'age'  => 42
        ];

        $maggy = $repo->store($attributes);
        $maggyData = $maggy->toArray();
        $this->assertModelInstance($maggy);
        $this->assertEquals($attributes['name'], $maggyData['name']);
        $this->assertEquals($attributes['age'], $maggyData['age']);
        $this->assertFalse($maggy->isNew());

        if ($hans instanceof GenericEntity) {
            $this->assertFalse($maggy->wasModified());
        }
        $this->assertEquals(2, $maggy->getId());

    }

    /**
     * @test
     * @expectedException \Ems\Contracts\Core\Errors\DataCorruption
     */
    public function store_throws_DataIntegrityException_if_store_did_fail()
    {
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

    /**
     * @test
     */
    public function get_returns_stored_objects()
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

        $this->assertEquals($hansData['name'], $repo->get(1)->toArray()['name']);
        $this->assertEquals($maggyData['name'], $repo->get(2)->toArray()['name']);

        $this->assertNull($repo->get(3));

    }

    /**
     * @test
     */
    public function update_saves_changes_in_object()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($attributes);
        $hansData = $hans->toArray();

        $this->assertEquals($hansData['name'], $repo->get(1)->toArray()['name']);

        $this->assertTrue($repo->update($hans, ['name' => 'Gustav']));

        $gustav = $repo->get(1);
        $gustavData = $gustav->toArray();

        $this->assertEquals($gustavData['name'], $hans->toArray()['name']);

        $this->assertFalse($repo->update($gustav, ['name' => 'Gustav']));

    }

    /**
     * @test
     */
    public function update_does_not_save_if_object_didnt_change()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($attributes);
        $hansData = $hans->toArray();

        $this->assertEquals($hansData['name'], $repo->get(1)->toArray()['name']);


        $this->assertFalse($repo->update($hans, ['name' => 'Hans']));

    }

    /**
     * @test
     */
    public function save_does_not_persist_if_object_didnt_change()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($attributes);

        $result = $repo->save($hans);

        // Can only detect changes with ChangeTracking...
        if ($hans instanceof ChangeTracking) {
            $this->assertFalse($result);
        } else {
            $this->assertTrue($result);
        }


    }

    /**
     * @test
     */
    public function delete_deletes_stored_object()
    {
        $repo = $this->newRepository();

        $attributes = [
            'name' => 'Hans',
            'age'  => 35
        ];

        $hans = $repo->store($attributes);
        $hansData = $hans->toArray();
        $this->assertModelInstance($hans);
        $this->assertEquals($attributes['name'], $hansData['name']);
        $this->assertEquals($attributes['age'], $hansData['age']);
        $this->assertFalse($hans->isNew());
        if ($hans instanceof GenericEntity) {
            $this->assertFalse($hans->wasModified());
        }
        $this->assertEquals(1, $hans->getId());

        $attributes = [
            'name' => 'Maggy',
            'age'  => 42
        ];

        $maggy = $repo->store($attributes);
        $maggyData = $maggy->toArray();

        $this->assertModelInstance($maggy);
        $this->assertEquals($attributes['name'], $maggyData['name']);
        $this->assertEquals($attributes['age'], $maggyData['age']);
        $this->assertFalse($maggy->isNew());
        if ($maggy instanceof GenericEntity) {
            $this->assertFalse($maggy->wasModified());
        }
        $this->assertEquals(2, $maggy->getId());

        $this->assertTrue($repo->delete($hans));

        $this->assertNull($repo->get(1));

    }

    protected function assertModelInstance($model)
    {
        $this->assertInstanceOf(GenericEntity::class, $model);
    }

    /**
     * @param PushableStorage        $storage (optional)
     * @param InputProcessorContract $caster (optional)
     *
     * @return StorageRepository
     */
    protected function newRepository(PushableStorage $storage=null, InputProcessorContract $caster=null)
    {
        $storage = $storage ?: $this->newPushableStorage();
        $caster = $caster ?: new InputProcessor();
        return new StorageRepository($storage, $caster);
    }

     /**
     *
     * @param Storage     $baseStorage (optional)
     * @param IdGenerator $idGenerator (optional)
     *
     * @return PushableProxyStorage
     */
    protected function newPushableStorage(Storage $baseStorage=null, IdGenerator $idGenerator=null)
    {
        $baseStorage = $baseStorage ?: $this->newBaseStorage();
        $idGenerator = $idGenerator ?: new IncrementingIdGenerator();
        return new PushableProxyStorage($baseStorage, $idGenerator);
    }

    protected function newBaseStorage($data=[])
    {
        return new ArrayStorage($data);
    }
}
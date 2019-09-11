<?php
/**
 *  * Created by mtils on 24.03.19 at 11:54.
 **/

namespace Ems\Core\Repositories;


use Ems\Contracts\Core\DataObject;
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
use Ems\Core\Support\DataObjectTrait;
use Ems\Foundation\InputProcessor;
use Ems\Model\OrmObject;
use Ems\TestCase;

require_once  __DIR__ . '/StorageRepositoryTest.php';

class StorageRepositoryDataObjectTest extends StorageRepositoryTest
{

    protected function assertModelInstance($model)
    {
        $this->assertInstanceOf(StorageRepositoryDataObjectTest_DataObject::class, $model);
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
        $repo = new StorageRepository($storage, $caster);
        $repo->setItemClass(StorageRepositoryDataObjectTest_DataObject::class);
        $repo->createObjectsBy(function (array $attributes=[], $isFromStorage=false) {
            $object = new StorageRepositoryDataObjectTest_DataObject();
            $id = isset($attributes['id']) ? $attributes['id'] : '';
            $object->hydrate($attributes, $id, $isFromStorage);
            return $object;
        });
        return $repo;
    }

}

class StorageRepositoryDataObjectTest_DataObject implements DataObject
{
    use DataObjectTrait;


    public function __construct()
    {
        $this->_properties = [
            'name' => '',
            'age'  => ''
        ];
    }

}
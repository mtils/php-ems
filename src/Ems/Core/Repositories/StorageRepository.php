<?php
/**
 *  * Created by mtils on 08.09.18 at 15:09.
 **/

namespace Ems\Core\Repositories;


use Ems\Contracts\Core\ChangeTracking;
use Ems\Contracts\Core\DataObject;
use Ems\Contracts\Core\Errors\NotFound;
use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Core\Provider;
use Ems\Contracts\Core\PushableStorage;
use Ems\Contracts\Core\Repository;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Core\Exceptions\DataIntegrityException;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Core\GenericEntity;
use Ems\Foundation\InputProcessor;

class StorageRepository implements Repository
{
    /**
     * @var PushableStorage
     */
    protected $storage;

    /**
     * @var InputProcessorContract
     */
    protected $caster;

    /**
     * @var callable
     */
    protected $objectFactory;

    /**
     * @var bool
     */
    protected $isBufferedStorage = false;

    /**
     * @var string
     */
    protected $itemClass = GenericEntity::class;

    public function __construct(PushableStorage $storage, InputProcessorContract $caster=null)
    {
        $this->storage = $storage;
        $this->caster = $caster ?: new InputProcessor();
        $this->isBufferedStorage = $storage->isBuffered();
        $this->init();
    }

    /**
     * {@inheritdoc}
     *
     * @see Provider::get()
     *
     * @param mixed $id
     * @param mixed $default (optional)
     *
     * @return DataObject
     **/
    public function get($id, $default = null)
    {
        try {
            return $this->getOrFail($id);
        } catch (NotFound $e) {
            //
        }
        return $default;
    }

    /**
     * {@inheritdoc}
     *
     * @see Provider::get()
     *
     * @param mixed $id
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     * @return DataObject
     **/
    public function getOrFail($id)
    {
        if (!$this->storage->offsetExists($id)) {
            throw new ResourceNotFoundException("Model with id #$id not found.");
        }
        return $this->deserializeFromStorage($this->storage->offsetGet($id));
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return DataObject The instantiated resource
     **/
    public function make(array $attributes = [])
    {
        return $this->newInstance($attributes, false);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return DataObject The created resource
     **/
    public function store(array $attributes)
    {
        $model = $this->newInstance($attributes);
        if ($this->save($model)) {
            return $model;
        }

        throw new DataIntegrityException("Cannot store attributes");
    }

    /**
     * {@inheritdoc}
     *
     * @param DataObject $model
     * @param array      $attributes
     *
     * @return bool if attributes where changed after filling
     **/
    public function fill(Identifiable $model, array $attributes)
    {

        $model = $this->checkTypeAndReturn($model);
        $modelAttributes = $model->toArray();
        $changedAttributes = [];

        foreach ($attributes as $key=>$value) {
            // Check for exact comparison
            if(isset($modelAttributes[$key]) && $modelAttributes[$key] === $value) {
                continue;
            }
            $changedAttributes[$key] = $value;
        }
        if (!$changedAttributes) {
            return false;
        }
        $model->apply($changedAttributes);
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param DataObject $model
     * @param array      $newAttributes
     *
     * @return bool true if it was actually saved, false if not. Look above!
     **/
    public function update(Identifiable $model, array $newAttributes)
    {
        if (!$this->fill($model, $newAttributes)) {
            return false;
        }
        return $this->save($model);
    }

    /**
     * {@inheritdoc}
     *
     * @param DataObject $model
     *
     * @return bool if the model was actually saved
     **/
    public function save(Identifiable $model)
    {
        $model = $this->checkTypeAndReturn($model);

        if ($model instanceof ChangeTracking && !$model->wasModified() && !$model->isNew()) {
            return false;
        }

        list($id, $data) = $this->toIdAndData($model);

        unset($id); // Make inspection happy

        $casted = $this->caster->process($data, $model);


        $id = $this->writeToStorage($model, $casted);

        if (!$this->persistIfNeeded()) {
            return false;
        }

        $model->hydrate($casted, $id);

        return true;

    }

    /**
     * {@inheritdoc}
     *
     * @param DataObject $model
     *
     * @return bool
     **/
    public function delete(Identifiable $model)
    {
        $model = $this->checkTypeAndReturn($model);
        $this->storage->offsetUnset($model->getId());
        return $this->persistIfNeeded();

    }
    /**
     * {@inheritdoc}
     *
     * @param callable $factory
     *
     * @return self
     **/
    public function createObjectsBy(callable $factory)
    {
        $this->objectFactory = $factory;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemClass()
    {
        return $this->itemClass;
    }

    /**
     * @param string $itemClass
     *
     * @return StorageRepository
     */
    public function setItemClass($itemClass)
    {
        $this->itemClass = $itemClass;
        return $this;
    }


    protected function init()
    {
        $this->createObjectsBy(function (array $attributes, $isFromStorage, $resourceName='') {
            $itemClass = $this->itemClass;
            return new $itemClass($attributes, $isFromStorage, $resourceName);
        });
    }
    /**
     * @param array $attributes
     * @param bool  $fromStorage (default:false)
     *
     * @return GenericEntity
     */
    protected function newInstance(array $attributes, $fromStorage=false)
    {
        return call_user_func($this->objectFactory, $attributes, $fromStorage, 'data-object');
    }

    /**
     * @param $data
     * @return GenericEntity
     */
    protected function deserializeFromStorage($data)
    {
        return $this->newInstance($data, true);
    }

    /**
     * @param DataObject $entity
     * @param array $castedData
     *
     * @return int|string
     */
    protected function writeToStorage(DataObject $entity, array $castedData)
    {
        if ($entity->isNew()) {
            return $this->storage->offsetPush($castedData);
        }

        $this->storage->offsetSet($entity->getId(), $castedData);
        return $entity->getId();

    }

    /**
     * @param Identifiable $model
     *
     * @return DataObject
     */
    protected function checkTypeAndReturn(Identifiable $model)
    {
        if (!$model instanceof DataObject) {
            throw new TypeException('Model has to be DataObject not ' . Type::of($model));
        }
        return $model;
    }

    protected function toIdAndData(DataObject $entity)
    {
        $data = $entity->toArray();
        if ($id = $entity->getId()) {
            return [$id, $data];
        }
        return [null, $data];

    }

    protected function persistIfNeeded()
    {
        if ($this->isBufferedStorage) {
            return $this->storage->persist();
        }
        // If the storage does auto persist and didn't throw an exception we
        // assume it as success
        return true;
    }
}
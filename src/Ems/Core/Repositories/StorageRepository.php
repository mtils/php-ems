<?php
/**
 *  * Created by mtils on 08.09.18 at 15:09.
 **/

namespace Ems\Core\Repositories;


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
     * @var bool
     */
    protected $isBufferedStorage = false;

    public function __construct(PushableStorage $storage, InputProcessorContract $caster=null)
    {
        $this->storage = $storage;
        $this->caster = $caster ?: new InputProcessor();
        $this->isBufferedStorage = $storage->isBuffered();
    }

    /**
     * {@inheritdoc}
     *
     * @see Provider::get()
     *
     * @param mixed $id
     * @param mixed $default (optional)
     *
     * @return GenericEntity
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
     * @return GenericEntity
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
     * @return GenericEntity The instantiated resource
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
     * @return GenericEntity The created resource
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
     * @param GenericEntity $model
     * @param array     $attributes
     *
     * @return bool if attributes where changed after filling
     **/
    public function fill(Identifiable $model, array $attributes)
    {

        $model = $this->checkTypeAndReturn($model);
        $changed = false;

        foreach ($attributes as $key=>$value) {
            // Check for exact comparison
            if(isset($model[$key]) && $model[$key] === $value) {
                continue;
            }
            $model[$key] = $value;
            $changed = true;
        }
        return $changed;
    }

    /**
     * {@inheritdoc}
     *
     * @param GenericEntity $model
     * @param array     $newAttributes
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
     * @param GenericEntity $model
     *
     * @return bool if the model was actually saved
     **/
    public function save(Identifiable $model)
    {
        $model = $this->checkTypeAndReturn($model);

        if (!$model->wasModified()) {
            return false;
        }

        list($id, $data) = $this->toIdAndData($model);

        unset($id); // Make inspection happy

        $casted = $this->caster->process($data, $model);


        $id = $this->writeToStorage($model, $casted);

        if (!$this->persistIfNeeded()) {
            return false;
        }

        $casted[$model->getIdKey()] = $id;

        $model->_fill($casted, true);

        return true;

    }

    /**
     * {@inheritdoc}
     *
     * @param GenericEntity $model
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
     * @param array $attributes
     * @param bool  $fromStorage (default:false)
     *
     * @return GenericEntity
     */
    protected function newInstance(array $attributes, $fromStorage=false)
    {
        return new GenericEntity($attributes, $fromStorage, 'data-object');
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
     * @param GenericEntity $entity
     * @param array $castedData
     *
     * @return int|string
     */
    protected function writeToStorage(GenericEntity $entity, array $castedData)
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
     * @return GenericEntity
     */
    protected function checkTypeAndReturn(Identifiable $model)
    {
        if (!$model instanceof GenericEntity) {
            throw new TypeException('Model has to be GenericEntity not ' . Type::of($model));
        }
        return $model;
    }

    protected function toIdAndData(GenericEntity $entity)
    {
        $id = $entity->getId();
        $data = $entity->toArray();
        if (isset($data[$entity->getIdKey()])) {
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
<?php

/**
 *  * Created by mtils on 29.03.20 at 19:40.
 **/

namespace Ems\Core;

use Ems\Contracts\Core\DataRepository;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Core\ObjectDataAdapter;
use Ems\Contracts\Core\Repository;
use Ems\Core\Exceptions\ResourceNotFoundException;

/**
 * Class GenericRepository
 *
 * This class is just a sample how to build you own repository implementations
 * with the ems base classes.
 *
 * @package Ems\Core
 */
class GenericRepository implements Repository
{
    /**
     * @var ObjectDataAdapter
     */
    private $objectAdapter;

    /**
     * @var DataRepository
     */
    private $storage;

    /**
     * Get an object by its id.
     *
     * @param mixed $id
     * @param mixed $default (optional)
     *
     * @return mixed
     **/
    public function get($id, $default = null)
    {
        if (!$data = $this->storage->get($id)) {
            return $default;
        }
        return $this->objectAdapter->fromArray($data, true);
    }

    /**
     * Get an object by its id or throw an exception if it cant be found.
     *
     * @param mixed $id
     *
     * @return mixed
     *
     * @throws ResourceNotFoundException
     */
    public function getOrFail($id)
    {
        if ($object = $this->get($id)) {
            return $object;
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Instantiate a new model and fill it with the attributes.
     *
     * @param array $attributes
     *
     * @return Identifiable The instantiated resource
     **/
    public function make(array $attributes = [])
    {
        return $this->objectAdapter->fromArray($attributes, false);
    }

    /**
     * Fill the model with attributes $attributes.
     *
     * @param Identifiable $model
     * @param array $attributes
     *
     * @return bool if attributes where changed after filling
     **/
    public function fill(Identifiable $model, array $attributes)
    {
        $this->objectAdapter->fill($model, $attributes);
        return true;
    }

    /**
     * Create a new model by the given attributes and persist
     * it.
     *
     * @param array $attributes
     *
     * @return Identifiable The created resource
     **/
    public function store(array $attributes)
    {
        $data = $this->storage->create($attributes);
        return $this->objectAdapter->fromArray($data, true);
    }

    /**
     * Update the model with $newAttributes
     * Return true if the model was saved, false if not. If an error did occur,
     * throw an exception. Never return false on errors. Return false if for
     * example the attributes did not change. Throw exceptions on errors.
     * If the save action did alter other attributes that the passed, the have
     * to be updated inside the passed model. (Timestamps, auto increments,...)
     * The passed model has to be full up to date after updating it.
     *
     * @param Identifiable $model
     * @param array $newAttributes
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
     * Persists the model $model. Always saves it without checks if the model
     * was actually changed before saving.
     * The model has to be filled (with auto attributes like auto increments or
     * timestamps).
     *
     * @param Identifiable $model
     *
     * @return bool if the model was actually saved
     **/
    public function save(Identifiable $model)
    {
        $data = $this->objectAdapter->toArray($model);
        $newData = $this->storage->update($data);
        return $this->fill($model, $newData);
    }

    /**
     * Delete the passed model.
     *
     * @param Identifiable $model
     *
     * @return bool
     **/
    public function delete(Identifiable $model)
    {
        return $this->storage->delete($this->objectAdapter->id($model));
    }
}

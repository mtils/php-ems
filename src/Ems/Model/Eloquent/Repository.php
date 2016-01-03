<?php 

namespace Ems\Model\Eloquent;

use InvalidArgumentException;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Core\Repository as RepositoryContract;
use Illuminate\Database\Eloquent\Model;

class Repository implements RepositoryContract
{

    protected $model;

    public function __construct(Model $model)
    {
        $this->checkIsIdentifiable($model);
        $this->model = $model;
    }

    /**
     * @inheritdoc
     *
     * @param mixed $id
     * @return \Ems\Contracts\Core\Identifiable|null
     **/
    public function get($id)
    {
        return $this->model->get($id);
    }

    /**
     * Find a model by its id. Throw a NotFoundException if not found
     *
     * @param mixed $id
     * @return \Ems\Contracts\Core\Identifiable
     * @throws \Ems\Contracts\Core\NotFound If no model was found by the id
     **/
    public function getOrFail($id)
    {
        if ($model = $this->get($id)) {
            return $model;
        }

        throw new NotFoundException("No results for id $id");
    }

    /**
     * Instantiate a new model and fill it with the attributes
     *
     * @param array $attributes
     * @return \Ems\Contracts\Core\Identifiable The instantiated resource
     **/
    public function make(array $attributes=[])
    {
        return $this->model->newInstance($attributes);
    }

    /**
     * Create a new model by the given attributes and persist
     * it
     *
     * @param array $attributes
     * @return \Ems\Contracts\Core\Identifiable The created resource
     **/
    public function store(array $attributes)
    {
        return $this->model->create($attributes);
    }

    /**
     * Fill the model with attributes $attributes
     *
     * @param \Ems\Contracts\Core\Identifiable $model
     * @param array $attributes
     * @return bool if attributes where changed after filling
     **/
    public function fill(Identifiable $model, array $attributes)
    {
        $model->fill($attributes);
        return true;
    }

    /**
     * Update the model with $newAttributes
     * Return true if the model was saved, false if not. If an error did occur,
     * throw an exception. Never return false on errors. Return false if for
     * example the attributes did not change. Throw exceptions on errors.
     * If the save action did alter other attributes that the passed, the have
     * to be updated inside the passed model. (Timestamps, autoincrements,...)
     * The passed model has to be full up to date after updating it
     *
     * @param \Ems\Contracts\Core\Identifiable $model
     * @param array $newAttributes
     * @return boolean true if it was actually saved, false if not. Look above!
     **/
    public function update(Identifiable $model, array $newAttributes)
    {

        $this->checkIsModel($model);

        if ($this->fill($model, $newAttributes)){
            return true;
        }

        return false;

    }

    /**
     * Persists the model $model. Always saves it without checks if the model
     * was actually changed before saving.
     * The model has to be filled (with auto attributes like autoincrements or
     * timestamps)
     *
     * @param \Ems\Contracts\Core\Identifiable $model
     * @return bool if the model was actually saved
     **/
    public function save(Identifiable $model)
    {
        $this->checkIsModel($model);
        return $model->save();
    }

    /**
     * Delete the passed model.
     *
     * @param \Ems\Contracts\Core\Identifiable $model
     * @return void
     **/
    public function delete(Identifiable $model)
    {
        $this->checkIsModel($model);
        $model->delete();
    }

    /**
     * Checks a model for the Identifiable interface
     *
     * @param mixed
     * @throws \InvalidArgumentException
     **/
    protected function checkIsIdentifiable($model)
    {
        if (!$model instanceof Identifiable) {
            throw new InvalidArgumentException('Model has to be instanceof Identifiable');
        }
    }

    /**
     * Checks a model for the Model class
     *
     * @param mixed
     * @throws \InvalidArgumentException
     **/
    protected function checkIsModel($model)
    {
        if (!$model instanceof Model) {
            throw new InvalidArgumentException('Identifiable has to be instanceof Eloquent Model');
        }
    }

}
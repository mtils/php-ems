<?php 

namespace Ems\Model\Eloquent;

use InvalidArgumentException;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Core\ExtendableRepository;
use Ems\Core\ExtendableRepositoryTrait;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Ems\Testing\Cheat;
use DateTime;

class Repository implements ExtendableRepository
{

    use ExtendableRepositoryTrait;

    protected $model;

    protected $jsonableAttributes = [];

    /**
     * @var callable
     **/
    protected $attributeFilter;

    public function __construct(EloquentModel $model)
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

        $query = $this->model->newQuery();

        $this->publish('getting', $query);

        if (!$model = $query->find($id)) {
            return;
        }

        $this->publish('got', $model);

        return $model;

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

        throw (new NotFoundException("No results for id $id"))->setModel(get_class($this->model));
    }

    /**
     * Instantiate a new model and fill it with the attributes
     *
     * @param array $attributes
     * @return \Ems\Contracts\Core\Identifiable The instantiated resource
     **/
    public function make(array $attributes=[])
    {
        $model = $this->model->newInstance($attributes);
        $this->publish('made', $model);
        return $model;
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

        $model = $this->make([]);

        $this->validate($attributes, 'store');

        $this->fill($model, $attributes);

        $this->publish('storing', $model, $attributes);
        $this->save($model);
        $this->publish('stored', $model, $attributes);

        return $model;
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
        $this->publish('filling', $model, $attributes);
        $filtered = $this->toModelAttributes($model, $attributes);
        $model->fill($filtered);
        $this->publish('filled', $model, $attributes);
        return true;
    }

    /**
     * Update the model with $newAttributes
     * Return true if the model was saved, false if not. If an error did occur,
     * throw an exception. Never return false on errors. Return false if for
     * example the attributes did not change. Throw exceptions on errors.
     * If the save action did alter other attributes then the passed, the have
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

        $this->validate($newAttributes, 'update');

        if (!$this->fill($model, $newAttributes)) {
            return false;
        }

        $this->publish('updating', $model, $newAttributes);

        $this->save($model);

        $this->publish('updated', $model, $newAttributes);

        return true;

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
        $this->publish('saving', $model);
        $result = $model->save();
        $this->publish('saved', $model);
        return $result;
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
        $this->publish('deleting', $model);
        $model->delete();
        $this->publish('deleted', $model);
    }

    /**
     * Assign a custim attributeFilter. A attributefilter is just a callable
     * which gets key and value passed and returns true to apply the attribute
     * and false to remove it
     *
     * @param callable $filter
     * @return self
     **/
    public function filterAttributesBy(callable $filter)
    {
        $this->attributeFilter = $filter;
        return $this;
    }

    /**
     * Return the attributes which should also be stored even if they are not scalar
     *
     * @return array
     **/
    public function getJsonableAttributes()
    {
        if ($this->jsonableAttributes !== null) {
            return $this->jsonableAttributes;
        }

        $this->jsonableAttributes = [];

        foreach(Cheat::get($this->model, 'casts') as $key=>$cast) {
            if (in_array($cast, ['array', 'json', 'object', 'collection'], true)) {
                $this->jsonableAttributes[] = $key;
            }
        }
        return $this->jsonableAttributes;

    }

    /**
     * Set jsonable attributes so they get not filtered while saving
     * attributes
     *
     * @param string|array $attributes
     * @return self
     **/
    public function setJsonableAttributes($attributes) {
        $this->jsonableAttributes = (array)$attributes;
        return $this;
    }

    /**
     * Return the internal attributefilter. If none is present, create one
     *
     * @return callable
     * @see self::filterAttributesBy
     **/
    protected function getAttributeFilter()
    {
        if ($this->attributeFilter) {
            return $this->attributeFilter;
        }
        return function($key, $value) {
            if (in_array($key, $this->getJsonableAttributes())) {
                return !is_scalar($value);
            }

            if (ends_with($key, '_id') && trim($value) === '') {
                return false;
            }

            return is_scalar($value) || $value instanceof DateTime;
        };
    }

    /**
     * Hook into this method to do some special validation, even if the data
     * have to be validated before passing it to the repository
     *
     * @param array $attributes
     * @param string $action
     * @return bool
     * @throws \Illuminate\Contracts\Validation\ValidationException
     **/
    protected function validate(array $attributes, $action='update'){}

    /**
     * Cast an clean the incoming attributes so that this repository can
     * savely pass them to the database.
     * The attributes have to be validated before passing them to this method.
     *
     * @param mixed $model
     * @param array $attributes
     * @return array
     **/
    protected function toModelAttributes($model, $attributes)
    {

        $filtered = [];
        $filter = $this->getAttributeFilter();

        foreach ($attributes as $key=>$value) {

            if (!$filter($key, $value)) {
                continue;
            }

            $filtered[$key] = $value;
        }
        return $filtered;
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
        if (!$model instanceof EloquentModel) {
            throw new InvalidArgumentException('Identifiable has to be instanceof Eloquent Model');
        }
    }

}

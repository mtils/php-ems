<?php


namespace Ems\Model\Eloquent;

use DateTime;
use InvalidArgumentException;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Model\HookableRepository as HookableRepositoryContract;
use Ems\Core\Patterns\HookableTrait;
use Ems\Testing\Cheat;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder;

class HookableRepository implements HookableRepositoryContract
{
    use HookableTrait;

    /**
     * @var EloquentModel
     **/
    protected $model;

    /**
     * @var array
     **/
    protected $nonScalarAttributes;

    /**
     * @var callable
     **/
    protected $attributeFilter;

    /**
     * @param EloquentModel
     **/
    public function __construct(EloquentModel $model)
    {
        $this->model = $this->checkIsIdentifiable($model);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $id
     * @param mixed $default (optional)
     *
     * @return \Ems\Contracts\Core\Identifiable|null
     **/
    public function get($id, $default = null)
    {
        $query = $this->model->newQuery();

        $this->callBeforeListeners('get', [$query]);

        if (!$model = $this->performGet($query, $id)) {
            return $default;
        }

        $this->callAfterListeners('get', [$model]);

        return $model;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $id
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound If no model was found by the id
     *
     * @return Identifiable
     **/
    public function getOrFail($id)
    {
        if ($model = $this->get($id)) {
            return $model;
        }

        throw (new NotFoundException("No results for id $id"))->setModel(get_class($this->model));
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return Identifiable The instantiated resource
     **/
    public function make(array $attributes = [])
    {
        $model = $this->performMake($attributes);
        $this->fill($model, $attributes);
        $this->callAfterListeners('make', [$model]);

        return $model;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return Identifiable The created resource
     **/
    public function store(array $attributes)
    {
        $model = $this->make([]);

        $this->validate($attributes, 'store');

        $this->fill($model, $attributes);

        $this->callBeforeListeners('store', [$model, $attributes]);
        $this->save($model);
        $this->callAfterListeners('store', [$model, $attributes]);

        return $model;
    }

    /**
     * {@inheritdoc}
     *
     * @param Identifiable $model
     * @param array        $attributes
     *
     * @return bool if attributes where changed after filling
     **/
    public function fill(Identifiable $model, array $attributes)
    {
        $this->callBeforeListeners('fill', [$model, $attributes]);
        $filtered = $this->toModelAttributes($model, $attributes);

        $beforeDirty = $model->isDirty();
        $this->performFill($model, $filtered);
        $changed = $beforeDirty || $model->isDirty();

        $this->callAfterListeners('fill', [$model, $attributes]);

        return $changed;
    }

    /**
     * {@inheritdoc}
     *
     * @param Identifiable $model
     * @param array        $newAttributes
     *
     * @return bool true if it was actually saved, false if not. Look above!
     **/
    public function update(Identifiable $model, array $newAttributes)
    {
        $this->checkIsModel($model);

        $this->validate($newAttributes, 'update');

        if (!$this->fill($model, $newAttributes)) {
            return false;
        }

        $this->callBeforeListeners('update', [$model, $newAttributes]);

        $this->save($model);

        $this->callAfterListeners('update', [$model, $newAttributes]);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param Identifiable $model
     *
     * @return bool if the model was actually saved
     **/
    public function save(Identifiable $model)
    {
        $this->checkIsModel($model);
        $this->callBeforeListeners('save', $model);
        $result = $this->performSave($model);
        $this->callAfterListeners('save', $model);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param Identifiable $model
     **/
    public function delete(Identifiable $model)
    {
        $this->checkIsModel($model);
        $this->callBeforeListeners('delete', $model);
        $this->performDelete($model);
        $this->callAfterListeners('delete', $model);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function methodHooks()
    {
        return ['get', 'make', 'store', 'fill', 'update', 'save', 'delete'];
    }

    /**
     * Assign a custim attributeFilter. A attributefilter is just a callable
     * which gets key and value passed and returns true to apply the attribute
     * and false to remove it.
     *
     * @param callable $filter
     *
     * @return self
     **/
    public function filterAttributesBy(callable $filter)
    {
        $this->attributeFilter = $filter;

        return $this;
    }

    /**
     * Actually perform the get operation. Easier to overwrite.
     *
     * @param Builder $query
     * @param mixed $id
     *
     * @return EloquentModel
     **/
    protected function performGet(Builder $query, $id)
    {
        return $query->find($id);
    }

    /**
     * Actually perform the make operation. Easier to overwrite.
     *
     * @param array $attributes
     *
     * @return EloquentModel
     **/
    protected function performMake(array $attributes)
    {
        return $this->model->newInstance();
    }

    /**
     * Actually perform the fill operation. Easier to overwrite.
     *
     * @param EloquentModel $model
     * @param array         $attributes
     *
     **/
    protected function performFill(EloquentModel $model, array $attributes)
    {
        $model->fill($attributes);
    }

    /**
     * Actually perform the save operation. Easier to overwrite.
     *
     * @param EloquentModel $model
     *
     * @return bool if the model was actually saved
     **/
    protected function performSave(EloquentModel $model)
    {
        return $model->save();
    }

    /**
     * {@inheritdoc}
     *
     * @param EloquentModel $model
     **/
    protected function performDelete(EloquentModel $model)
    {
        $model->delete();
    }

    /**
     * Hook into this method to do some special validation, even if the data
     * have to be validated before passing it to the repository.
     *
     * @param array  $attributes
     * @param string $action
     *
     * @throws \Illuminate\Contracts\Validation\ValidationException
     *
     * @return bool
     **/
    protected function validate(array $attributes, $action = 'update')
    {
        return true;
    }

    /**
     * Cast an clean the incoming attributes so that this repository can
     * savely pass them to the database.
     * The attributes have to be validated before passing them to this method.
     *
     * @param mixed $model
     * @param array $attributes
     *
     * @return array
     **/
    protected function toModelAttributes($model, $attributes)
    {
        $filtered = [];
        $filter = $this->getAttributeFilter();

        foreach ($attributes as $key => $value) {
            if (!$filter($key, $value)) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

     /**
     * Return the internal attributefilter. If none is present, create one.
     *
     * @return callable
     *
     * @see self::filterAttributesBy
     **/
    protected function getAttributeFilter()
    {
        if ($this->attributeFilter) {
            return $this->attributeFilter;
        }

        return function ($key, $value) {
            if (in_array($key, $this->getNonScalarAttributes())) {
                return !is_scalar($value);
            }

            if (ends_with($key, '_id') && trim($value) === '') {
                return false;
            }

            return is_scalar($value) || $value instanceof DateTime;
        };
    }

    /**
     * Return the attributes which should also be stored even if they are not scalar.
     *
     * @return array
     **/
    public function getNonScalarAttributes()
    {
        if ($this->nonScalarAttributes === null) {
            $this->nonScalarAttributes = $this->loadNonScalarAttributes();
        }

        return $this->nonScalarAttributes;
    }

    /**
     * Set not-scalar attributes so they get not filtered while saving
     * attributes.
     *
     * @param string|array $attributes
     *
     * @return self
     **/
    public function setNonScalarAttributes($attributes)
    {
        $this->nonScalarAttributes = (array) $attributes;

        return $this;
    }

    /**
     * Loads jsonable attributes so they get not filtered while saving
     * attributes.
     *
     * @return array
     **/
    protected function loadNonScalarAttributes()
    {

        $nonScalar = [];

        foreach (Cheat::get($this->model, 'casts') as $key => $cast) {
            if (in_array($cast, ['array', 'json', 'object', 'collection'], true)) {
                $nonScalar[] = $key;
            }
        }

        return $nonScalar;

    }

    /**
     * Checks a model for the Identifiable interface.
     *
     * @param mixed
     *
     * @throws InvalidArgumentException
     **/
    protected function checkIsIdentifiable($model)
    {
        if ($model instanceof Identifiable) {
            return $model;
        }
        throw new InvalidArgumentException('Model has to be instanceof Identifiable');
    }

    /**
     * Checks a model is an Eloquent Model
     *
     * @param mixed
     *
     * @throws \InvalidArgumentException
     **/
    protected function checkIsModel($model)
    {
        if (!$model instanceof EloquentModel) {
            throw new InvalidArgumentException('Identifiable has to be instanceof Eloquent Model');
        }
    }
}

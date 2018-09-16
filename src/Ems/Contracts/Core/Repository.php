<?php
/**
 *  * Created by mtils on 08.09.18 at 15:00.
 **/

namespace Ems\Contracts\Core;


/**
 * A generic repository to work with models.
 * Were Core\Storage is the simplest data store a repository
 * is used if you have a unique data structure (objects) of all data you
 * put into the repository.
 * It also must do some basic casting/checking if the data is valid before
 * saving it.
 *
 *
 **/
interface Repository extends Provider
{
    /**
     * Instantiate a new model and fill it with the attributes.
     *
     * @param array $attributes
     *
     * @return Identifiable The instantiated resource
     **/
    public function make(array $attributes = []);

    /**
     * Create a new model by the given attributes and persist
     * it.
     *
     * @param array $attributes
     *
     * @return Identifiable The created resource
     **/
    public function store(array $attributes);

    /**
     * Fill the model with attributes $attributes.
     *
     * @param Identifiable $model
     * @param array        $attributes
     *
     * @return bool if attributes where changed after filling
     **/
    public function fill(Identifiable $model, array $attributes);

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
     * @param array        $newAttributes
     *
     * @return bool true if it was actually saved, false if not. Look above!
     **/
    public function update(Identifiable $model, array $newAttributes);

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
    public function save(Identifiable $model);

    /**
     * Delete the passed model.
     *
     * @param Identifiable $model
     *
     * @return bool
     **/
    public function delete(Identifiable $model);
}

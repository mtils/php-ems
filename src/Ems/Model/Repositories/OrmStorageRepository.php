<?php
/**
 *  * Created by mtils on 26.05.19 at 17:47.
 **/

namespace Ems\Model\Repositories;

use Ems\Contracts\Core\Identifiable;
use Ems\Core\Repositories\StorageRepository;
use Ems\Model\OrmObject;
use Ems\Contracts\Model\OrmObject as OrmObjectContract;

/**
 * Class OrmStorageRepository
 *
 * This is basically the same as \Ems\Core\Repositories\StorageRepository.
 * It just works with OrmObjects.
 *
 * @package Ems\Model
 */
class OrmStorageRepository extends StorageRepository
{
    /**
     * @var string
     */
    protected $itemClass = OrmObject::class;

    /**
     * @var callable
     */
    protected $lazyLoader;

    /**
     * {@inheritdoc}
     *
     * @param mixed $id
     * @param mixed $default (optional)
     *
     * @return OrmObjectContract
     *
     */
    public function get($id, $default = null)
    {
        return parent::get($id, $default);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $id
     *
     * @return OrmObjectContract
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     */
    public function getOrFail($id)
    {
        return parent::getOrFail($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return OrmObject The instantiated resource
     **/
    public function make(array $attributes = [])
    {
        return parent::make($attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return OrmObject The created resource
     **/
    public function store(array $attributes)
    {
        return parent::store($attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @param OrmObject $model
     * @param array     $attributes
     *
     * @return bool if attributes where changed after filling
     **/
    public function fill(Identifiable $model, array $attributes)
    {
        return parent::fill($model, $attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @param OrmObject $model
     * @param array     $newAttributes
     *
     * @return bool true if it was actually saved, false if not. Look above!
     **/
    public function update(Identifiable $model, array $newAttributes)
    {
        return parent::update($model, $newAttributes);
    }

    /**
     * {@inheritdoc}
     *
     * @param OrmObject $model
     *
     * @return bool if the model was actually saved
     **/
    public function save(Identifiable $model)
    {
        return parent::save($model);
    }

    /**
     * {@inheritdoc}
     *
     * @param OrmObject $model
     *
     * @return bool
     **/
    public function delete(Identifiable $model)
    {
        return parent::delete($model);
    }

    protected function init()
    {
        $this->createObjectsBy(function (array $attributes, $fromStorage, $resourceName='') {
            $class = $this->itemClass;
            return new $class($attributes, $fromStorage, $this->lazyLoader);
        });
    }


    /**
     * @param array $attributes
     * @param bool $fromStorage (default:false)
     *
     * @return OrmObject
     */
    protected function newInstance(array $attributes, $fromStorage = false)
    {
        return parent::newInstance($attributes, $fromStorage);
    }

    /**
     * Return the lazy loader, on object that lazy loads any attributes in an
     * orm object that was not loaded.
     *
     * @return callable
     */
    public function getLazyLoader()
    {
        return $this->lazyLoader;
    }

    /**
     * @see self::getLazyLoader()
     *
     * @param callable $lazyLoader
     *
     * @return OrmStorageRepository
     */
    public function setLazyLoader(callable $lazyLoader)
    {
        $this->lazyLoader = $lazyLoader;
        return $this;
    }

}
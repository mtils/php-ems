<?php
/**
 *  * Created by mtils on 19.12.19 at 11:53.
 **/

namespace Ems\Model\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Trait ProxyModelTrait
 *
 * Use this to make the active record nature of eloquent a little more data
 * mapper.
 *
 *
 *
 * @package Ems\Model\Eloquent
 */
trait ProxyModelTrait
{
    /**
     * @var EloquentModel
     */
    protected $model;

    /**
     * @return EloquentModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param EloquentModel $model
     *
     * @return $this;
     */
    public function setModel(EloquentModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->model->__isset($name);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->model->__get($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->model->__set($name, $value);
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        $this->model->__unset($name);
    }
}
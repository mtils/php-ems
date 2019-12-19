<?php
/**
 *  * Created by mtils on 19.12.19 at 12:00.
 **/

namespace Ems\Model\Eloquent\Contracts;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Interface ProxyModel
 *
 * Use this interface in combination with the Ems\Model\Eloquent\ProxyModelTrait
 * to work with implementation-less value objects.
 *
 * So you can just write a class, use the ProxyModelTrait, implement this
 * interface and you have an implementation-less value object.
 *
 * @package Ems\Model\Eloquent\Contracts
 */
interface ProxyModel
{
    /**
     * Return the underlying model.
     *
     * @return EloquentModel
     */
    public function getModel();

    /**
     * Set the underlying model.
     *
     * @param EloquentModel $model
     *
     * @return static;
     */
    public function setModel(EloquentModel $model);
}
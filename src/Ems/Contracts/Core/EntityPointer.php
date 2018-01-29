<?php
/**
 *  * Created by mtils on 26.01.18 at 17:10.
 **/

namespace Ems\Contracts\Core;

/**
 * Class EntityPointer
 *
 * This is a little helper class to store a pointer for an entity.
 * It is useful to serialize and deserialize entities or any class that can
 * be identified by its class and an id.
 *
 * @package Ems\Contracts\Core
 */
class EntityPointer
{

    /**
     * The class of the entity.
     *
     * @var string
     */
    public $type = '';

    /**
     * The entity id.
     *
     * @var string|int
     */
    public $id = 0;

    /**
     * If you need a more specific pointer add a hash.
     * (like spl_object_hash($entity)).
     *
     * @var string
     */
    public $hash = '';

}
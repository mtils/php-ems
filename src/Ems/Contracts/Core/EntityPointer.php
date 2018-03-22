<?php
/**
 *  * Created by mtils on 26.01.18 at 17:10.
 **/

namespace Ems\Contracts\Core;
use function json_decode;
use Serializable;

/**
 * Class EntityPointer
 *
 * This is a little helper class to store a pointer for an entity.
 * It is useful to serialize and deserialize entities or any class that can
 * be identified by its class and an id.
 *
 * @package Ems\Contracts\Core
 */
class EntityPointer implements Serializable
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

    /**
     * EntityPointer constructor.
     *
     * @param string     $type
     * @param string|int $id
     * @param string     $hash
     */
    public function __construct($type='', $id=0, $hash='')
    {
        $this->type = $type;
        $this->id = $id;
        $this->hash = $hash;
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return json_encode([$this->type, $this->id]);
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list($type, $id) = json_decode($serialized);
        $this->type = $type;
        $this->id = $id;
    }


}
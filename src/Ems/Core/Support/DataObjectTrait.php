<?php
/**
 *  * Created by mtils on 11.09.19 at 08:49.
 **/

namespace Ems\Core\Support;

use Ems\Contracts\Core\Exceptions\WriteToReadonlyKeyException;
use function is_bool;

/**
 * Trait DataObjectTrait
 *
 * This is a small helper to make your object a (minimum) DataObject
 *
 * @package Ems\Core\Support
 *
 * @see \Ems\Contracts\Core\DataObject
 */
trait DataObjectTrait
{
    /**
     * Here is all data held. Fill it in __construct
     *
     * @var array
     */
    protected $_properties = [];

    /**
     * @var bool
     */
    protected $_isFromStorage = false;

    /**
     * The name of the primary key property.
     *
     * @var string
     */
    protected $_idKey = 'id';

    /**
     * Return the unique identifier for this object.
     *
     * @see \Ems\Contracts\Core\Identifiable
     *
     * @return int|string|null
     */
    public function getId()
    {
        if (isset($this->_properties[$this->_idKey])) {
            return $this->_properties[$this->_idKey];
        }
        return null;
    }

    /**
     * Return if this object is from storage (exists) or was just instantiated.
     *
     * @return bool
     */
    public function isNew()
    {
        return !$this->_isFromStorage;
    }

    /**
     * Hydrate the object by $data. Optionally pass an id. If an id was passed
     * the object assumes to be hydrated from storage. If the passed id === null
     * it assumes it is new.
     * If you want to overwrite the "from storage or not"-behaviour pass a boolean
     * third $forceFromStorage attribute.
     *
     * Hydrate means: Completely clear the object and refill it.
     *
     * @param array      $data
     * @param int|string $id (optional)
     * @param bool       $forceIsFromStorage (optional)
     *
     * @return void
     *
     * @see \Ems\Contracts\Core\Hydratable
     */
    public function hydrate(array $data, $id=null, $forceIsFromStorage=null)
    {
        if ($id === null) {
            $id = isset($data[$this->_idKey]) ? $data[$this->_idKey] : null;
        }
        if ($id) {
            $data[$this->_idKey] = $id;
        }

        $isFromStorage = is_bool($forceIsFromStorage) ? $forceIsFromStorage : (bool)$id;

        $this->hydrateProperties($data, $isFromStorage);
    }

    /**
     * Fill the object with the passed data. Do not clear the object before you
     * fill it.
     *
     * @param array $data
     *
     * @return self
     *
     * @see \Ems\Contracts\Core\Hydratable
     */
    public function apply(array $data)
    {
        foreach ($data as $key=>$value) {
            $this->applyProperty($key, $value);
        }
        return $this;
    }

    /**
     * This is a performance related method. In this method
     * you should implement the fastest was to get every
     * key and value as an array.
     * Only the root has to be an array, it should not build
     * the array by recursion.
     *
     * @see \Ems\Contracts\Core\Arrayable
     *
     * @return array
     **/
    public function toArray()
    {
        return $this->_properties;
    }

    /**
     * @param array $data
     * @param bool $isFromStorage
     */
    protected function hydrateProperties(array $data, $isFromStorage)
    {
        $this->_properties = $data;
        $this->_isFromStorage = $isFromStorage;
    }

    /**
     * Apply a single property. For simple adjustments to the apply method.
     *
     * @param string $key
     * @param mixed  $value
     */
    protected function applyProperty($key, $value)
    {
        if ($key == $this->_idKey) {
            throw new WriteToReadonlyKeyException('You cannot manually apply the ' . $this->_idKey . '.');
        }
        $this->_properties[$key] = $value;
    }
}
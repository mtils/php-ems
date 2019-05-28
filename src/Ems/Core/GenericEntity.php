<?php

namespace Ems\Core;

use Ems\Contracts\Core\ArrayWithState as ArrayWithStateContract;
use Ems\Contracts\Core\DataObject;
use Ems\Contracts\Core\Entity;
use Ems\Core\Support\TrackedArrayDataTrait;
use function is_bool;

class GenericEntity implements Entity, ArrayWithStateContract, DataObject
{
    use TrackedArrayDataTrait;

    protected $idKey = 'id';

    /**
     * @var string
     */
    protected $resourceName = '';

    /**
     * GenericEntity constructor.
     *
     * @param array $attributes
     * @param bool $isFromStorage
     * @param string $resourceName
     * @param string $idKey
     */
    public function __construct(array $attributes=[], $isFromStorage=false, $resourceName = '', $idKey='id')
    {
        $this->hydrate($attributes, null, $isFromStorage);
        $this->resourceName = $resourceName;
        $this->idKey = $idKey;
        if ($attributes) {
            $this->_booted = true; // Skip autoloading of attribute when filled here
        }
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->offsetExists($this->idKey) ? $this->offsetGet($this->idKey) : null;
    }

    /**
     * @inheritDoc
     */
    public function resourceName()
    {
        return $this->resourceName;
    }

    /**
     * Get the key of the id value in its array.
     *
     * @return string
     */
    public function getIdKey()
    {
        return $this->idKey;
    }

    /**
     * {@inheritDoc}
     *
     * @param array $data
     * @param int|string $id (optional)
     * @param bool $forceIsFromStorage (optional)
     *
     * @return void
     */
    public function hydrate(array $data, $id = null, $forceIsFromStorage = null)
    {
        if ($id === null) {
            $id = isset($data[$this->idKey]) ? $data[$this->idKey] : null;
        }
        if ($id) {
            $data[$this->idKey] = $id;
        }
        $isFromStorage = is_bool($forceIsFromStorage) ? $forceIsFromStorage : (bool)$id;
        $this->fillAttributes($data, $isFromStorage);
    }

    /**
     * {@inheritDoc}
     *
     * @param array $data
     *
     * @return self
     */
    public function apply(array $data)
    {
        foreach ($data as $key=>$value) {
            $this->offsetSet($key, $value);
        }
        return $this;
    }

}

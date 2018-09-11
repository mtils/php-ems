<?php

namespace Ems\Core;

use Ems\Contracts\Core\Entity;
use Ems\Contracts\Core\ArrayWithState as ArrayWithStateContract;
use Ems\Core\Support\TrackedArrayDataTrait;

class GenericEntity implements Entity, ArrayWithStateContract
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
        $this->_fill($attributes, $isFromStorage);
        $this->resourceName = $resourceName;
        $this->idKey = $idKey;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->offsetGet($this->idKey);
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
     * This is a pseudo protected method and should only be called from the
     * storage/repository.
     *
     * @param array $attributes
     * @param bool $isFromStorage (default:true)
     *
     * @return $this
     */
    public function _fill(array $attributes, $isFromStorage=true)
    {
        return $this->fillAttributes($attributes, $isFromStorage);
    }
}

<?php

namespace Ems\Model\Eloquent;

/**
 * @see \Ems\Contracts\Core\Entity
 **/
trait EntityTrait
{

    /**
     * @var string
     **/
    protected $_resourceName;

    /**
     * Return the unique identifier for this object.
     *
     * @return mixed (int|string)
     *
     * @see \Ems\Contracts\Core\Identifiable
     **/
    public function getId()
    {
        return $this->getKey();
    }

    /**
     * Return the unique identifier for this object.
     *
     * @return mixed (int|string)
     **/
    public function resourceName()
    {
        if (!$this->_resourceName) {
            $this->_resourceName = str_replace('_', '-', $this->getTable());
        }
        return $this->_resourceName;
    }

    /**
     * Return true if this instance is not from data store.
     *
     * @return bool
     **/
    public function isNew()
    {
        return !$this->exists;
    }

    /**
     * Return true if something was modified. Pass null to know
     * if the object was modified at all.
     * Pass a string to check if one attribute was modified. Pass
     * an array of attribute nams to now if ANY of them were
     * modified.
     *
     * @param string|array $attributes (optional)
     *
     * @return bool
     **/
    public function wasModified($attributes=null)
    {
        return $this->isDirty($attributes);
    }
}

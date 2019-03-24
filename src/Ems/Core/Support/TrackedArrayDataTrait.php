<?php

namespace Ems\Core\Support;

trait TrackedArrayDataTrait
{
    use BootingArrayData {
        BootingArrayData::fillAttributes as baseFillAttributes;
    }

    /**
     * The original filled attributes.
     *
     * @var array
     **/
    protected $_originalAttributes = [];

    /**
     * @var bool
     **/
    protected $_loadedFromStorage = false;

    /**
     * Fill the attributes with $attributes
     *
     * @param array $attributes
     * @param bool  $isFromStorage (default=true)
     *
     * @return self
     **/
    protected function fillAttributes(array $attributes, $isFromStorage=true)
    {
        if ($isFromStorage) {
            $this->_originalAttributes = $attributes;
        }
        $this->baseFillAttributes($attributes);
        $this->_loadedFromStorage = $isFromStorage;
        return $this;
    }

    /**
     * Return if the object is new (and not loaded from
     * storage)
     *
     * @return bool
     **/
    public function isNew()
    {
        $this->bootOnce();
        return !$this->_loadedFromStorage;
    }

    /**
     * Return if the object was modified ($keys=null) or
     * the key $keys was modified or
     * if ANY of the passed $keys (array) where modified.
     * Modified means different then in storage.
     *
     * @param string|array $keys (optional)
     *
     * @return bool
     **/
    public function wasModified($keys=null)
    {
        $this->bootOnce();

        $modified = $this->getModifiedData();
        $unsettedKeys = $this->getUnsettedKeys();

        if (!$keys) {
            return (bool)$modified || (bool)$unsettedKeys;
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {

            if (array_key_exists($key, $modified)) {
                return true;
            }

            if (in_array($key, $unsettedKeys)) {
                return true;
            }
        }

        return false;

    }

    /**
     * Return if the $keys were loaded from storage.
     * Pass a string for one key, pass an array to check
     * if ALL of the keys were loaded, pass nothing to
     * check if the object was loaded at all (!isNew)
     *
     * @param string|array $keys (optional)
     *
     * @return bool
     **/
    public function wasLoaded($keys=null)
    {
        // bootOnce is triggered by isNew() here

        if (!$keys) {
            return !$this->isNew();
        }

        if ($this->isNew()) {
            return false;
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->_originalAttributes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return the "original" value of $key.
     *
     * @param string|null $key (optional)
     * @param mixed       $default (optional)
     *
     * @return mixed
     **/
    public function getOriginal($key = null, $default = null)
    {
        $this->bootOnce();

        if (!$key) {
            return $this->_originalAttributes;
        }

        if (array_key_exists($key, $this->_originalAttributes)) {
            return $this->_originalAttributes[$key];
        }

        return $default;

    }

    /**
     * Reset the state to the original.
     *
     * @return self
     **/
    public function reset()
    {
        $this->_attributes = $this->_originalAttributes;
        $this->onReset();
        return $this;
    }

    /**
     * Return all modified keys. This is useful for saving data in storages
     * which support partial updates like in sql.
     *
     * @return array
     **/
    protected function getModifiedData()
    {
        $modified = [];

        foreach ($this->_attributes as $key=>$value) {

            // Every previously not existing key is modified
            if (!array_key_exists($key, $this->_originalAttributes)) {
                $modified[$key] = $value;
                continue;
            }

            if ($this->valueWasModified($this->_originalAttributes[$key], $this->_attributes[$key])) {
                $modified[$key] = $value;
            }
        }

        return $modified;
    }

    /**
     * Return all unsetted keys. You could set them all to zero in storage.
     *
     * @return array
     **/
    protected function getUnsettedKeys()
    {
        $unsettedKeys = [];

        foreach ($this->_originalAttributes as $key=>$value) {

            if (!array_key_exists($key, $this->_attributes)) {
                $unsettedKeys[] = $key;
                continue;
            }
        }

        return $unsettedKeys;
    }

    /**
     * Check if a value was modified
     *
     * @param mixed $originalValue
     * @param mixed $currentValue
     *
     * @return bool
     **/
    protected function valueWasModified($originalValue, $currentValue)
    {
        return $originalValue !== $currentValue;
    }

    /**
     * Use this hook to do things on reset.
     **/
    protected function onReset()
    {
        //
    }
}

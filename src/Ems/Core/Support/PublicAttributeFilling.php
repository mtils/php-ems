<?php

namespace Ems\Core\Support;

/**
 * This trait is used with BootingArrayData.
 * Use this trait if you need public fill support in your class. This is
 * normally not the case. Its mainly used in proxies.
 **/
trait PublicAttributeFilling
{
    /**
     * @var array
     **/
    protected $_attributesAssigner = null;

    /**
     * Assign a callable which will provide the attributes.
     *
     * @param callable $assigner
     *
     * @return self
     **/
    public function autoAssignAttributesBy(callable $assigner)
    {
        $this->_attributesAssigner = $assigner;
        return $this;
    }

    /**
     * A public method to assign the fillAttributes method.
     *
     * @param array $attributes
     * @param bool  $isFromStorage (default=true)
     *
     * @return self
     **/
    public function fill(array $attributes, $isFromStorage=true)
    {
        return $this->fillAttributes($attributes, $isFromStorage);
    }

    /**
     * Get attributes from provider or an empty array.
     *
     * @return void
     **/
    protected function autoAssignAttributes()
    {
        if ($this->_attributesAssigner) {
            call_user_func($this->_attributesAssigner, $this);
            return;
        }

        $attributes = isset($this->defaultAttributes) ? $this->defaultAttributes : [];

        // Default attributes are not counted as "from storage"
        $this->fillAttributes($attributes, false);
    }
}

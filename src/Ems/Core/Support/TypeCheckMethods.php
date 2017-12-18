<?php

namespace Ems\Core\Support;

use BadMethodCallException;
use Ems\Contracts\Core\Type;
use InvalidArgumentException;

/**
 * This trait delivers methods for easily deal with type checks and enforcing
 *
 * @property string $forceType Define this property to determine the type
 * @property bool $typeIsFrozen Define this property to disallow changes of $forcedType
 **/
trait TypeCheckMethods
{
    /**
     * Return the forced type.
     *
     * @return string
     **/
    public function getForcedType()
    {
        return $this->forceType;
    }

    /**
     * Return the forced type of this object.
     *
     * Allowed values are: bool,int,float,string,resource,array,object
     * All other strings will be treatet as class names
     *
     * @param string $type
     *
     * @return self
     **/
    public function setForcedType($type)
    {
        if ($this->typeIsFrozen()) {
            throw new BadMethodCallException("Cannot change frozen type {$this->forceType}");
        }
        $this->forceType = $type;

        return $this;
    }

    /**
     * Disallow any changes to the setted type
     * (can not be reversed).
     *
     * @return self
     **/
    public function freezeType()
    {
        $this->typeIsFrozen = true;

        return $this;
    }

    /**
     * Check if the type was frozen.
     *
     * @return bool
     **/
    public function typeIsFrozen()
    {
        return $this->typeIsFrozen;
    }

    /**
     * Does the actual type checks.
     *
     * @param mixed $value
     *
     * @return bool
     **/
    public function hasAllowedType($value)
    {
        if (!$this->forceType) {
            return true;
        }

        return Type::is($value, $this->forceType);
    }

    /**
     * Checks the type of any added item and throws an exception
     * if it does not match.
     *
     * @param mixed $value
     *
     * @return mixed
     **/
    protected function checkType($value)
    {
        if (!$this->hasAllowedType($value)) {
            throw new InvalidArgumentException("You can only add values of '{$this->forceType}' to this object");
        }
        return $value;
    }
}

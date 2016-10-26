<?php


namespace Ems\Core\Collections;

use InvalidArgumentException;
use Traversable;
use BadMethodCallException;

class TypeEnforcedList extends OrderedList
{

    /**
     * @var string
     **/
    protected $forceType = 'string';

    /**
     * @var bool
     **/
    protected $typeIsFrozen = false;

    /**
     * Return the forced type
     *
     * @return string
     **/
    public function getForcedType()
    {
        return $this->forceType;
    }

    /**
     * Return the forced type of this list
     *
     * Allowed values are: bool,int,float,string,resource,array,object
     * All other strings will be treatet as class names
     *
     * @param string $type
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
     * (can not be reversed)
     *
     * @return self
     **/
    public function freezeType()
    {
        $this->typeIsFrozen = true;
        return $this;
    }

    /**
     * Check if the type was frozen
     *
     * @return bool
     **/
    public function typeIsFrozen()
    {
        return $this->typeIsFrozen;
    }

    /**
     * Insert a value at position $index
     *
     * @param int $index
     * @param mixed $value
     * @return self
     **/
    public function insert($index, $value)
    {
        $this->checkType($value);
        return parent::insert($index, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param array|\Traversable|int|string $source (optional)
     * @return self
     **/
    public function setSource($source)
    {
        $array = $this->castToArray($source);

        foreach ($array as $item) {
            $this->append($item);
        }

        return $this;
    }

    /**
     * Does the actual type checks
     *
     * @param mixed $value
     * @return bool
     **/
    public function canAdd($value)
    {

        if (!$this->forceType) {
            return true;
        }

        switch ($this->forceType) {
            case 'bool':
                return is_bool($value);
            case 'int':
                return is_int($value);
            case 'float':
                return is_float($value);
            case 'string':
                return is_string($value);
            case 'resource':
                return is_resource($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value);
            default:
                return ($value instanceof $this->forceType);
        }

    }

    /**
     * {@inheritdoc}
     * Copies the type but not the freeze
     *
     * @return self
     */
    public function copy()
    {
        return (new static($this->source))->setForcedType($this->forceType);
    }

    /**
     * Checks the type of any added item and throws an exception
     * if it does not match
     *
     * @param mixed $value
     * @return null
     **/
    protected function checkType($value)
    {
        if (!$this->canAdd($value)) {
            throw new InvalidArgumentException("You can only add values of '{$this->forceType}' to this list");
        }
    }

    /**
     * Append a value to the end of this list
     *
     * @param mixed $value
     * @return self
     **/
    protected function addItem($value)
    {
        $this->checkType($value);
        return parent::addItem($value);
    }
}

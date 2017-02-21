<?php


namespace Ems\XType;

use ArrayAccess;
use DateTime;
use Ems\Contracts\XType\TypeFactory as TypeFactoryContract;
use Ems\Core\Helper;

/**
 * The TemplateTypeFactory can be used to create a type of all
 * passed variables. If you pass an int you get a NumberType, pass
 * an array and it will return an ArrayAccessType.
 * I would strongly suggest to NOT add this to the TypeFactoryChain
 * because it will create XType object for just anything you
 * give it and this will sooner or later skip your own factories and
 * leads to think anything you pass to it is a type configuration.
 * This class is just mainly used as a fallback for TypeProvider
 **/
class TemplateTypeFactory implements TypeFactoryContract
{

    /**
     * {@inheritdoc}
     *
     * @param mixed $config
     *
     * @return bool
     **/
    public function canCreate($config)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $config
     *
     * @return \Ems\Contracts\XType\XType
     **/
    public function toType($config)
    {
        return $this->convertType($config)->fill(['defaultValue'=>$config]);
    }

    /**
     * @param mixed $value
     *
     * @return AbstractType
     **/
    protected function convertType($value)
    {
        switch(true) {

            case is_bool($value):
                return new BoolType;

            case is_int($value):
                return new NumberType(['nativeType'=>'int']);

            case is_float($value):
                return new NumberType(['nativeType'=>'float']);

            case is_null($value):
                return new StringType(['canBeNull'=>true]);

            case $value instanceof DateTime:
                return new TemporalType;

            case $this->isSequence($value):
                return $this->createSequence($value);

            case is_object($value):
                return $this->createObject($value);

            case $this->hasKeysAndValues($value):
                return $this->createArrayType($value);

            default:
                return new StringType;
        }

    }

    /**
     * @param object $value
     *
     * @return BoolType
     **/
    protected function createObject($object)
    {
        $type = new ObjectType(['class'=>get_class($object)]);

        foreach (get_object_vars($object) as $property=>$value) {
            $type[$property] = $this->toType($value);
        }

        return $type;
    }

    /**
     * @param object|array $array
     *
     * @return BoolType
     **/
    protected function createArrayType($array)
    {
        $type = new ArrayAccessType;

        foreach ($array as $key=>$value) {
            $type[$key] = $this->toType($value);
        }

        return $type;
    }

    /**
     * @param object|array $value
     *
     * @return SequenceType
     **/
    protected function createSequence($value)
    {
        return new SequenceType(['itemType'=>$this->toType(Helper::first($value))]);
    }

    /**
     * Return true if the passed value is a sequence (array or class
     * which have strict numeric keys and every item type is the same.
     *
     * @param mixed $value
     *
     * @return bool
     **/
    protected function isSequence($value)
    {
        if (!Helper::isSequential($value)) {
            return false;
        }

        if ($value === []) {
            return true;
        }

        $i=0;

        $lastItemType = null;

        foreach ($value as $key=>$item) {

            if ($key !== $i) {
                return false;
            }

            $itemType = Helper::typeName($item);

            // TODO: list could be typed by common class or interface
            if ($i !== 0 && $itemType != $lastItemType) {
                return false;
            }

            $lastItemType = $itemType;
            $i++;
        }

        return true;

    }

    /**
     * @param mixed $value
     *
     * @return bool
     **/
    protected function hasKeysAndValues($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

}

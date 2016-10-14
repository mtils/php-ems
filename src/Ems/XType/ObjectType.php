<?php


namespace Ems\XType;


class ObjectType extends NamedFieldType
{

    /**
     * The class of this object
     *
     * @var string
     **/
    public $class;

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function group()
    {
        return self::CUSTOM;
    }

}

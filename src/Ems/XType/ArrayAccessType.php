<?php


namespace Ems\XType;


class ArrayAccessType extends NamedFieldType
{

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function group()
    {
        return self::COMPLEX;
    }

}

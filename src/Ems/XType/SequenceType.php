<?php

namespace Ems\XType;

class SequenceType extends TypeWithLength
{
    /**
     * @var \Ems\Contracts\XType\XType
     **/
    public $itemType;

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

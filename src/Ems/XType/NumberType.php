<?php

namespace Ems\XType;

class NumberType extends TypeWithLength
{
    /**
     * @var float
     **/
    public $default = 0;

    /**
     * The native php type (float|int).
     *
     * @var string
     **/
    public $nativeType = 'int';

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function group()
    {
        return self::NUMBER;
    }
}

<?php

namespace Ems\XType;

use Ems\Contracts\XType\HasMinMax;

class NumberType extends AbstractType implements HasMinMax
{
    use MinMaxProperties;

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

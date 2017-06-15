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
     * With how many decimal places are the values STORED (money:4)?
     *
     * @var int
     **/
    public $precision = 0;

    /**
     * With how many decimal places are the values typically displayed (money:2)?
     *
     * @var int
     **/
    public $decimalPlaces = 0;

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

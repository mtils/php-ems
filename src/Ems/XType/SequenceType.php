<?php

namespace Ems\XType;

use Ems\Contracts\XType\HasMinMax;

class SequenceType extends AbstractType implements HasMinMax
{
    use MinMaxProperties;

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

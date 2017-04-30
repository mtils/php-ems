<?php

namespace Ems\XType;

use Ems\Contracts\XType\HasMinMax;
use Ems\Contracts\XType\HasTypedItems;

class SequenceType extends AbstractType implements HasMinMax, HasTypedItems
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

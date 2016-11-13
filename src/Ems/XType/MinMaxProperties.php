<?php

namespace Ems\XType;

/**
 * @see \Ems\Contracts\XType\HasMinMax
 **/
trait MinMaxProperties
{
    /**
     * @var int|float
     **/
    public $min = 0;

    /**
     * @var int|float
     **/
    public $max = 10000000;
}

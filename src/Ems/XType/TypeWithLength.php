<?php

namespace Ems\XType;

abstract class TypeWithLength extends AbstractType
{
    /**
     * Describes the minimum length. Applies to strings, numbers and
     * sequences.
     *
     * @var int
     **/
    public $min = 0;

    /**
     * Describes the maximum length. Applies to strings, numbers and
     * sequences.
     *
     * @var int
     **/
    public $max = 10000000;
}

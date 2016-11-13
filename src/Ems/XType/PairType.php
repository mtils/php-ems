<?php

namespace Ems\XType;

use Ems\Contracts\XType\HasMinMax;
use InvalidArgumentException;

class PairType extends AbstractType implements HasMinMax
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

    /**
     * Return immutable min and max values.
     *
     * @param string $name
     *
     * @return int
     **/
    public function __get($name)
    {
        if ($name == 'min') {
            return 2;
        }

        if ($name == 'max') {
            return 2;
        }
    }

    /**
     * Set (not) the value of $name.
     *
     * @param string $name
     * @param mixed  $value
     **/
    public function __set($name, $value)
    {
        if (in_array($name, ['min', 'max']) && $value != 2) {
            throw new InvalidArgumentException('A pair can only have exactly a length of 2');
        }
    }
}

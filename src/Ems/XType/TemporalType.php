<?php


namespace Ems\XType;

use Ems\Contracts\Core\TemporalUnit;


class TemporalType extends AbstractType
{

    /**
     * Is this a year, a month and so on?
     * TemporalUnit::SECOND would be a Timestamp
     *
     * @var string
     **/
    public $precision = TemporalUnit::SECOND;

    /**
     * Is this only a month without a year?
     * then it would be absolute = false. Normally it is absolute
     *
     * @var bool
     **/
    public $absolute = true;

    /**
     * The point in time, can be past|now|future
     *
     * @var string
     **/
    public $pointInTime = '';

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function group()
    {
        return self::TEMPORAL;
    }

}

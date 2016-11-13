<?php

namespace Ems\XType\UnitTypes;

use Ems\XType\UnitType;
use Ems\Contracts\Core\PointInTime;

/**
 * A TemporalUnit is a relative temporal statement. It says hour 8 without
 * a date (like in a cron date)
 * The unit is the type of statement. See Ems\Contracts\Core\PointInTime for a
 * list of constants (units).
 **/
class TemporalUnitType extends UnitType
{
    /**
     * {@inheritdoc}
     *
     * @var string
     **/
    public $unit = PointInTime::YEAR;
}

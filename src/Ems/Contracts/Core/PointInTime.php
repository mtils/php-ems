<?php

namespace Ems\Contracts\Core;

interface PointInTime
{
    const YEAR = 'Y';

    const MONTH = 'm';

    const DAY = 'd';

    const HOUR = 'H';

    const MINUTE = 'i';

    const SECOND = 's';

    const TIMEZONE = 'e';

    const WEEKDAY = 'l';

    /**
     * Return the precision (self::YEAR|self::MONTH...).
     *
     * @return string
     *
     * @see self::YEAR
     **/
    public function precision();

    /**
     * Return the timezone.
     *
     * @return \DateTimeZone
     **/
    public function getTimeZone();

    /**
     * Return the timezone offset.
     *
     * @return int
     **/
    public function getOffset();

    /**
     * Format the date to a string.
     *
     * @param string
     *
     * @return string
     **/
    public function format($format);

    /**
     * Modify the date.
     *
     * @param string
     **/
    public function modify($string);

    /**
     * Set year, month and day.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @return self
     **/
    public function setDate($year, $month, $day);

    /**
     * Set hour, minute and second.
     *
     * @param int $hour
     * @param int $minute
     * @param int $second (optional)
     *
     * @return self
     **/
    public function setTime($hour, $minute, $second = 0);

    /**
     * You can invalidate a PointInTime Object. Normally there is no
     * "null"-state of a DateTime object. But in many cases I just
     * want to have a invalid DateTime object.
     *
     * @return bool
     */
    public function isValid();

    /**
     * Make this datetime object invalid.
     *
     * @param bool $makeInvalid
     *
     * @return self
     */
    public function invalidate($makeInvalid=true);

    /**
     * Use properties for all units
     * Must support year, month, day, hour, minute, second, timezone, timestamp,
     * offset, unit.
     *
     * @param mixed $property
     *
     * @return int
     **/
    public function __get($property);

    /**
     * Use properties for all units.
     *
     * @see self::__get() for a list of supported names
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return int
     **/
    public function __set($property, $value);

    /**
     * Return true on allowed properties.
     *
     * @see self::__get() for a list of supported names
     *
     * @param string $property
     *
     * @return bool
     **/
    public function __isset($property);
}

<?php


namespace Ems\Core;


use DateTime;
use DateTimeZone;
use Ems\Contracts\Core\TemporalUnit;

class GenericTemporalUnit extends DateTime implements TemporalUnit
{

    /**
     * @var string
     **/
    protected $unit;

    protected $properties = [
        'year'      => self::YEAR,
        'month'     => self::MONTH,
        'day'       => self::DAY,
        'hour'      => self::HOUR,
        'minute'    => self::MINUTE,
        'second'    => self::SECOND,
        'timezone'  => 'getTimeZone',
        'timestamp' => 'getTimestamp',
        'offset'    => 'getOffset',
        'unit'      => 'unit'
    ];

    protected $propertyAccess = [
        'year'      => 'format',
        'month'     => 'format',
        'day'       => 'format',
        'hour'      => 'format',
        'minute'    => 'format',
        'second'    => 'format',
        'timezone'  => 'getTimeZone',
        'timestamp' => 'getTimestamp',
        'offset'    => 'getOffset',
        'unit'      => 'unit'
    ];

    /**
     * @param string|null              $time
     * @param DateTimeZone|string|null $tz
     */
    public function __construct($time = null, $tz = null)
    {
        parent::__construct($time, $tz);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @see self::YEAR
     **/
    public function unit()
    {
        if ($this->unit) {
            return $this->unit;
        }
        return self::SECOND;
    }

    /**
     * Set what this unit is (self::SECOND)
     *
     * @param string $unit
     **/
    public function setUnit($unit)
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $property
     * @return int
     **/
    public function __get($property)
    {
        if (!isset($this->properties[$property])) {
            throw new OutOfBoundsException("Property $property is not supported");
        }

        switch ($property) {
            case 'year':
            case 'month':
            case 'day':
            case 'hour':
            case 'minute':
            case 'second':
                    return (int)$this->format($this->properties[$property]);
            case 'timezone':
            case 'timestamp':
            case 'offset':
            case 'unit':
                    return call_user_func([$this, $this->properties[$property]]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $property
     * @param mixed $value
     * @return null
     **/
    public function __set($property, $value)
    {
        if (!isset($this->properties[$property])) {
            throw new OutOfBoundsException("Property $property is not supported");
        }

        switch ($property) {
            case 'year':
                $this->setDate($value, $this->month, $this->day);
                return;
            case 'month':
                $this->setDate($this->year, $value, $this->day);
                return;
            case 'day':
                $this->setDate($this->year, $this->month, $value);
                return;
            case 'hour':
                $this->setTime($value, $this->minute, $this->second);
                return;
            case 'minute':
                $this->setTime($this->hour, $value, $this->second);
                return;
            case 'second':
                $this->setTime($this->hour, $this->minute, $value);
                return;
            case 'timezone':
                $this->setTimeZone($value);
                return;
            case 'timestamp':
                $this->setTimestamp($value);
                return;
            case 'offset':
                throw new OutOfBoundsException("You cannot set an offset, assign a new DateTimeZone");
                return;
            case 'unit':
                $this->setUnit($value);
        }

    }

    /**
     * {@inheritdoc}
     *
     * @param string $property
     * @return bool
     **/
    public function __isset($property)
    {
        return isset($this->properties[$property]);
    }


    public static function createFromFormat($format, $string, $timezone=null)
    {
        $timezone = $timezone ?: new DateTimeZone(date_default_timezone_get());
        $other = DateTime::createFromFormat($format, $string, $timezone);
        return (new static)->setTimestamp($other->getTimestamp())
                           ->setTimezone($other->getTimezone());
    }

}

<?php

namespace Ems\Core;

use Ems\Contracts\Core\Localizer;
use InvalidArgumentException;
use DateTime;
use ArrayAccess;

/**
 * Class ArrayLocalizer
 *
 * @package Ems\Core
 * @deprecated use \Ems\Core\Formatter
 */
class ArrayLocalizer implements Localizer
{
    /**
     * @param array|ArrayAccess $config (optional)
     **/
    public function __construct($config = [])
    {
        $config = $config ?: $this->defaultConfig();
        $this->setConfig($config);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int|float $number
     * @param int              $decimals
     *
     * @return string
     **/
    public function number($number, $decimals = 0)
    {
        return number_format(
            (float) $number,
            $decimals,
            $this->get('number')['decimal_separator'],
            $this->get('number')['thousands_separator']
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed  $date
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function date($date, $verbosity = self::SHORT)
    {
        return $this->toDateTime($date)->format($this->get('date')[$verbosity]);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed  $date
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function time($date, $verbosity = self::SHORT)
    {
        return $this->toDateTime($date)->format($this->get('time')[$verbosity]);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed  $date
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function dateTime($date, $verbosity = self::SHORT)
    {
        return $this->toDateTime($date)->format($this->get('date_time')[$verbosity]);
    }

    /**
     * {@inheritdoc}
     *
     * @param int    $number
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function weekDay($number, $verbosity = self::VERBOSE)
    {
        return $this->get('week_days')[$verbosity][$number];
    }

    /**
     * {@inheritdoc}
     *
     * @param int    $number
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function month($number, $verbosity = self::VERBOSE)
    {
        return $this->get('months')[$verbosity][$number];
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int|float $amount
     *
     * @return string
     **/
    public function money($amount, $decimals = 2, $sourceCurrency = null)
    {
        return str_replace(
            ['{number}', '{currency}'],
            [$this->number($decimals), $this->currency()],
            $this->get('money')['format']
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function currency($verbosity = self::SHORT)
    {
        return $this->get('currency')[$verbosity];
    }

    /**
     * {@inheritdoc}
     *
     * @param int|float $length
     * @param int       $decimals
     * @param string    $sourceUnit (optional)
     *
     * @return string
     **/
    public function length($length, $decimals = 0, $sourceUnit = null)
    {
        $data = $this->get('length');
        $unit = $sourceUnit ?: $data['default_unit'];
        $number = $this->number($length, $decimals);

        return str_replace(
            ['{number}', '{unit}'],
            [$number, $unit],
            $data['format']
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function measuring()
    {
        return $this->get('length')['measuring'];
    }

    /**
     * Set the config (array).
     *
     * @param array|\ArrayAccess $config
     *
     * @return self
     **/
    public function setConfig($config)
    {
        if (!is_array($config) && !$config instanceof ArrayAccess) {
            throw new InvalidArgumentException('Config has to be array or ArrayAccess');
        }
        $this->config = $config;

        return $this;
    }

    /**
     * Gets a value from config.
     *
     * @param string $key
     *
     * @return mixed
     **/
    protected function get($key)
    {
        return $this->config[$key];
    }

    /**
     * Turns an arbitary argument into a date.
     *
     * @param mixed $date
     *
     * @return \DateTime
     **/
    protected function toDateTime($date)
    {
        return PointInTime::guessFrom($date);
    }

    /**
     * Return a default config.
     *
     * @return array
     **/
    protected function defaultConfig()
    {
        return [
            'number' => [
                'decimal_separator'   => '.',
                'thousands_separator' => ',',
            ],
            'date' => [
                'short'   => 'm/d/Y',
                'long'    => 'F d, Y',
                'verbose' => 'F D the d, Y',
            ],
            'time' => [
                'short'   => 'h:i A',
                'long'    => 'at h:i A',
                'verbose' => 'at h:i:s A',
            ],
            'date_time' => [
                'short'   => 'm/d/Y h:i A',
                'long'    => 'F d, Y at h:i A',
                'verbose' => 'F D the d, Y at h:i:s A',
            ],
            'week_days' => [
                'short' => [
                    1 => 'Mo.',
                    2 => 'Tu.',
                    3 => 'We.',
                    4 => 'Th.',
                    5 => 'Fr.',
                    6 => 'Sa.',
                    7 => 'Su.',
                ],
                'long' => [
                    1 => 'Mon.',
                    2 => 'Tue.',
                    3 => 'Wed.',
                    4 => 'Thu.',
                    5 => 'Fri.',
                    6 => 'Sat.',
                    7 => 'Sun.',
                ],
                'verbose' => [
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                    7 => 'Sunday',
                ],
            ],
            'months' => [
                'short' => [
                    1  => 'J',
                    2  => 'F',
                    3  => 'M',
                    4  => 'A',
                    5  => 'M',
                    6  => 'J',
                    7  => 'J',
                    8  => 'A',
                    9  => 'S',
                    10 => 'O',
                    11 => 'N',
                    12 => 'D',
                ],
                'long' => [
                    1  => 'Jan',
                    2  => 'Feb',
                    3  => 'Mar',
                    4  => 'Apr',
                    5  => 'May',
                    6  => 'Jun',
                    7  => 'Jul',
                    8  => 'Aug',
                    9  => 'Sep',
                    10 => 'Oct',
                    11 => 'Nov',
                    12 => 'Dec',
                ],
                'verbose' => [
                    1  => 'January',
                    2  => 'February',
                    3  => 'March',
                    4  => 'April',
                    5  => 'May',
                    6  => 'June',
                    7  => 'July',
                    8  => 'August',
                    9  => 'September',
                    10 => 'October',
                    11 => 'November',
                    12 => 'December',
                ],
            ],
            'money' => [
                'format' => '{number} {currency}',
            ],
            'currency' => [
                'short'   => '$',
                'long'    => 'Dollars',
                'verbose' => 'American Dollars',
            ],
            'length' => [
                'measuring'    => 'imperial',
                'format'       => '{number} {unit}',
                'default_unit' => 'inch',
            ],
        ];
    }
}

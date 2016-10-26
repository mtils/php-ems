<?php



namespace Ems\Contracts\Core;


interface Localizer
{

    /**
     * Short output for date|time|dateTime (2016/01/05 10:00)
     *
     * @var string
     **/
    const SHORT = 'short';

    /**
     * Long output for date|time|dateTime (2016/01/05 at 10:00)
     *
     * @var string
     **/
    const LONG = 'long';

    /**
     * Verbose output for date|time|dateTime (2016 May 05 10:00)
     *
     * @var string
     **/
    const VERBOSE = 'verbose';

    /**
     * Format a number with decimal and thousands separators
     *
     * @param string|int|float $number
     * @param int $decimals
     * @return string
     **/
    public function number($number, $decimals=0);

    /**
     * Format a date (only Y,m,d)
     *
     * @param mixed $date
     * @param string $verbosity (optional)
     * @return string
     **/
    public function date($date, $verbosity=self::SHORT);

    /**
     * Format time (only H,i(,s))
     *
     * @param mixed $date
     * @param string $verbosity (optional)
     * @return string
     **/
    public function time($date, $verbosity=self::SHORT);

    /**
     * Format date and time (y,m,d,H,i(,s))
     *
     * @param mixed $date
     * @param string $verbosity (optional)
     * @return string
     **/
    public function dateTime($date, $verbosity=self::SHORT);

    /**
     * Return the name of WeekDay number $number (1=Monday)
     *
     * @param int $number
     * @param string $verbosity (optional)
     * @return string
     **/
    public function weekDay($number, $verbosity=self::VERBOSE);

    /**
     * Return the name of month $number (1=January)
     *
     * @param int $number
     * @param string $verbosity (optional)
     * @return string
     **/
    public function month($number, $verbosity=self::VERBOSE);

    /**
     * Format a monetary amount
     *
     * @param string|int|float $amount
     **/
    public function money($amount, $decimals=2, $sourceCurrency=null);

    /**
     * Return the current currency symbol
     *
     * @param string $verbosity (optional)
     * @return string
     **/
    public function currency($verbosity=self::SHORT);

    /**
     * Format a length amount
     *
     * @param int|float $length
     * @param int $decimals
     * @param string $sourceUnit (optional)
     * @return string
     **/
    public function length($length, $decimals=0, $sourceUnit=null);

    /**
     * Return the type of measuring system what is used
     * for example imperial (american) or metric (european)
     *
     * @return string
     **/
    public function measuring();
}

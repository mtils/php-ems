<?php
/**
 *  * Created by mtils on 24.11.17 at 05:05.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface Formatter
 *
 * The formatter is the central place to format text. It has predefined
 * methods for date, time and number formatting and has to be extendable
 * so that any method can be added to it.
 *
 * You should add all your formatting methods via the extendable interface
 * to it:
 *
 * $formatter->extend('words', function ($text, $wordCount) {});
 *
 * Then you can use is as follows:
 *
 * $shorter = $formatter->words($text, 50);
 *
 * Or like this:
 *
 * $shorter = $formatter->format($text, 'words:50');
 *
 * With the second syntax you can easily build formatting chains in your views,
 * that makes the whole thing much more readable:
 *
 * $shorter = $formatter->format($text, 'trim|words:50|plain|quote:"');
 *
 * Because it uses formatting codes (like Y-m-d) you can also get this codes via
 * getFormat($name) and setFormat($name, $format).
 *
 * The formatter must only return plain text. (No html)
 *
 * @package Ems\Contracts\Core
 */
interface Formatter extends Extendable
{
    /**
     * Short output for date|time|dateTime (2016/01/05 10:00).
     *
     * @var string
     **/
    const SHORT = 'short';

    /**
     * Long output for date|time|dateTime (2016/01/05 at 10:00).
     *
     * @var string
     **/
    const LONG = 'long';

    /**
     * Verbose output for date|time|dateTime (Tuesday, 2016 May 05 10:00).
     *
     * @var string
     **/
    const VERBOSE = 'verbose';

    /**
     * The DATE format code
     *
     * @var string
     */
    const DATE = 'date';

    /**
     * The TIME format code
     *
     * @var string
     */
    const TIME = 'time';

    /**
     * The DATETIME format code
     *
     * @var string
     */
    const DATETIME = 'datetime';

    /**
     * The MONTH format code
     *
     * @var string
     */
    const MONTH = 'month';

    /**
     * The WEEKDAY format code
     *
     * @var string
     */
    const WEEKDAY = 'weekday';

    /**
     * The NUMBER format code
     *
     * @var string
     */
    const NUMBER = 'number';

    /**
     * The DECIMAL_MARK
     *
     * @var string
     */
    const DECIMAL_MARK = 'decimal_mark';

    /**
     * The THOUSANDS_SEPARATOR
     *
     * @var string
     */
    const THOUSANDS_SEPARATOR = 'thousands_separator';

    /**
     * The UNIT format code
     *
     * @var string
     */
    const UNIT = 'unit';

    /**
     * The MONEY format code
     *
     * @var string
     */
    const MONEY = 'money';

    /**
     * Format the $text with the passed $filters
     * Filters can be an array of filter names or a pipe
     * separated string.
     *
     * @example $tf->format($text, 'trim|escape|words:30')
     * This would be resolved into:
     * [
     *    'trim',
     *    'escape',
     *    'words' => [30]
     * ]
     *
     * @param mixed        $text
     * @param array|string $filters
     *
     * @return string
     **/
    public function format($text, $filters = []);

    /**
     * Directly call a filter.
     *
     * @param string $filter
     * @param array  $parameters (optional)
     *
     * @return string
     **/
    public function __call($filter, array $parameters = []);

    /**
     * Format a number with decimal and thousands separators.
     *
     * @param string|int|float $number
     * @param int              $decimals (optional)
     *
     * @return string
     **/
    public function number($number, $decimals = null);

    /**
     * Format a date (only Y,m,d).
     *
     * @param mixed  $date
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function date($date, $verbosity = self::SHORT);

    /**
     * Format time (only H,i(,s)).
     *
     * @param mixed  $date
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function time($date, $verbosity = self::SHORT);

    /**
     * Format date and time (y,m,d,H,i(,s)).
     *
     * @param mixed  $date
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function dateTime($date, $verbosity = self::SHORT);

    /**
     * Return the name of WeekDay number $number (1=Monday).
     *
     * @param int    $number
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function weekday($number, $verbosity = self::VERBOSE);

    /**
     * Return the name of month $number (1=January).
     *
     * @param int    $number
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function month($number, $verbosity = self::VERBOSE);

    /**
     * Format a unit. (3.6 inch).
     *
     * @param int|float $number
     * @param string    $unit (Should be a unit sign)
     * @param int       $decimals (optional)
     *
     * @return string
     **/
    public function unit($number, $unit, $decimals = null);

    /**
     * Format a monetary amount. (3,600$).
     *
     * @param int|float $number
     * @param string    $currency
     * @param int       $decimals (optional)
     *
     * @return string
     **/
    public function money($number, $currency, $decimals = null);

    /**
     * Return the format code for $name in $verbosity.
     *
     * @param string $name
     * @param string $verbosity (default: self::SHORT)
     *
     * @return string
     */
    public function getFormat($name, $verbosity=self::SHORT);

    /**
     * Set the format code for $name.
     *
     * @param string $name
     * @param string $format
     * @param string $verbosity (default: self::SHORT)
     *
     * @return $this
     */
    public function setFormat($name, $format, $verbosity=self::SHORT);

    /**
     * Get a symbol. A symbol is something like a weekday, separator which is
     * static.
     *
     * @param string     $name
     * @param string|int $value (optional)
     * @param string     $verbosity (default: self::SHORT)
     *
     * @return mixed
     */
    public function getSymbol($name, $value=null, $verbosity=self::SHORT);

    /**
     * Set a symbol. So to set the verbose name for weekday you would call:
     * $formatter->setSymbol(self::WEEKDAY, 1, 'Monday', self::VERBOSE).
     *
     * @param string     $name
     * @param string|int $value
     * @param string     $symbol
     * @param string     $verbosity (default: self::SHORT)
     *
     * @return mixed
     */
    public function setSymbol($name, $value, $symbol, $verbosity=self::SHORT);
}
<?php
/**
 * Created by mtils on 24.11.17 at 05:46.
 **/

namespace Ems\Core;

use DateTime;
use Ems\Contracts\Core\Formatter as FormatterContract;
use Ems\Contracts\Core\Multilingual;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Patterns\ExtendableTrait;
use Ems\Core\Support\StringChainSupport;
use Ems\Core\Exceptions\HandlerNotFoundException;
use InvalidArgumentException;

class Formatter implements FormatterContract, Multilingual
{
    use ExtendableTrait;
    use StringChainSupport;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var array
     */
    protected $localeFallbacks = [];

    /**
     * @var array|\ArrayAccess
     */
    protected $formats = [];

    /**
     * @var array
     */
    protected $formatCache = [];

    /**
     * @var array
     */
    protected $overwrites = [];

    /**
     * @var array
     */
    protected $dateFormatCache = [];

    /**
     * @var array
     */
    protected $localeSequence = [];

    /**
     * @var string
     */
    protected $formatsPrefix = 'formats.';

    /**
     * Formatter constructor.
     *
     * @param array|\ArrayAccess $formats
     * @param array $overwrites (optional)
     */
    public function __construct($formats=[], array $overwrites=[])
    {
        $this->setFormats($formats);
        $this->overwrites = $overwrites;
    }

    /**
     * @inheritdoc
     *
     * @param mixed $text
     * @param array|string $filters
     *
     * @return string
     **/
    public function format($text, $filters = [])
    {
        $formatted = $text;

        foreach ($this->parseChain($filters) as $name => $data) {
            $parameters = $data['parameters'];
            array_unshift($parameters, $formatted);

            $formatted = $this->__call($name, $parameters);
        }

        return $formatted;
    }

    /**
     * @inheritdoc
     *
     * @param string|int|float $number
     * @param int $decimals (optional)
     *
     * @return string
     **/
    public function number($number, $decimals = null)
    {
        return number_format(
            (float) $number,
            $decimals,
            $this->getSymbol(self::DECIMAL_MARK),
            $this->getSymbol(self::THOUSANDS_SEPARATOR)
        );
    }

    /**
     * @inheritdoc
     *
     * @param mixed $date
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function date($date, $verbosity = self::SHORT)
    {
        $dateTime = $this->toDateTime($date);
        $dateFormat = $this->getDateFormat(self::DATE, $verbosity, $dateTime);
        return $dateTime->format($dateFormat);
    }

    /**
     * @inheritdoc
     *
     * @param mixed $date
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function time($date, $verbosity = self::SHORT)
    {
        return $this->toDateTime($date)->format(
            $this->getFormat(self::TIME, $verbosity)
        );
    }

    /**
     * @inheritdoc
     *
     * @param mixed $date
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function dateTime($date, $verbosity = self::SHORT)
    {
        $dateTime = $this->toDateTime($date);
        $dateFormat = $this->getDateFormat(self::DATETIME, $verbosity, $dateTime);
        return $dateTime->format($dateFormat);
    }

    /**
     * @inheritdoc
     *
     * @param int $number
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function weekday($number, $verbosity = self::VERBOSE)
    {
        return $this->getSymbol(self::WEEKDAY, $number, $verbosity);
    }

    /**
     * @inheritdoc
     *
     * @param int $number
     * @param string $verbosity (optional)
     *
     * @return string
     **/
    public function month($number, $verbosity = self::VERBOSE)
    {
        return $this->getSymbol(self::MONTH, $number, $verbosity);
    }

    /**
     * @inheritdoc
     *
     * @param int|float $number
     * @param string $unit (Should be a unit sign)
     * @param int $decimals (optional)
     *
     * @return string
     **/
    public function unit($number, $unit, $decimals = null)
    {
        $format = $this->getFormat(self::UNIT);
        $numeric = $this->number($number, $decimals);
        return str_replace(['{number}', '{unit}'], [$numeric, $unit], $format);
    }

    /**
     * @inheritdoc
     *
     * @param int|float $number
     * @param string $currency
     * @param int $decimals (optional)
     *
     * @return string
     **/
    public function money($number, $currency, $decimals = null)
    {
        $format = $this->getFormat(self::MONEY);
        $numeric = $this->number($number, $decimals);
        return str_replace(['{number}', '{currency}'], [$numeric, $currency], $format);
    }

    /**
     * @inheritdoc
     *
     * @param string $name
     * @param string $verbosity (default: self::SHORT)
     *
     * @return string
     */
    public function getFormat($name, $verbosity = self::SHORT)
    {

        $key = $this->getFormatKey($name, $verbosity);

        if (!isset($this->formatCache[$key])) {
            $this->formatCache[$key] = $this->loadFormat($key);
        }

        return $this->formatCache[$key];

    }

    /**
     * @inheritdoc
     *
     * CAUTION: This does apply to all locales.
     *
     * @param string $name
     * @param string $format
     * @param string $verbosity (default: self::SHORT)
     *
     * @return FormatterContract
     */
    public function setFormat($name, $format, $verbosity = self::SHORT)
    {
        $key = $this->getFormatKey($name, $verbosity);
        $this->overwrites[$key] = $format;
        if (isset($this->formatCache[$key])) {
            unset($this->formatCache[$key]);
        }

        if (isset($this->dateFormatCache[$name][$verbosity])) {
            unset($this->dateFormatCache[$name][$verbosity]);
        }

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @param string     $name
     * @param string|int $value (optional)
     * @param string     $verbosity (default: self::SHORT)
     *
     * @return mixed
     */
    public function getSymbol($name, $value=null, $verbosity = FormatterContract::SHORT)
    {
        $key = $this->getSymbolKey($name, $value, $verbosity);

        if (!isset($this->formatCache[$key])) {
            $this->formatCache[$key] = $this->loadFormat($key);
        }

        if ($name == self::WEEKDAY || $name == self::MONTH) {
            $this->dateFormatCache = [];
        }

        return $this->formatCache[$key];
    }

    /**
     * @inheritdoc
     *
     * @param string     $name
     * @param string|int $value
     * @param string     $symbol
     * @param string     $verbosity (default: self::SHORT)
     *
     * @return mixed
     */
    public function setSymbol($name, $value, $symbol, $verbosity = FormatterContract::SHORT)
    {
        $key = $this->getSymbolKey($name, $value, $verbosity);
        $this->overwrites[$key] = $symbol;
        if (isset($this->formatCache[$key])) {
            unset($this->formatCache[$key]);
        }
        return $this;
    }


    /**
     * Return the configuration of all formats.
     *
     * @return array|\ArrayAccess
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * Set all the format definitions.
     *
     * @param array|\ArrayAccess $formats
     *
     * @return $this
     */
    public function setFormats($formats)
    {
        $this->formats = Helper::forceArrayAccess($formats);
        $this->formatCache = [];
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $locale
     * @param string|array $fallbacks (optional)
     *
     * @return self
     **/
    public function forLocale($locale, $fallbacks = null)
    {
        $copy = new static($this->getFormats(), $this->overwrites);
        $fallbacks = $fallbacks ? (array)$fallbacks: $this->localeFallbacks;
        return $copy->setLocale($locale)->setFallbacks($fallbacks);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $locale
     *
     * @return self
     **/
    public function setLocale($locale)
    {
        $this->locale = $locale;
        $this->localeSequence = [];
        $this->formatCache = [];
        $this->dateFormatCache = [];
        return $this;
    }

    /**
     * Return the fallback locales.
     *
     * @return array
     */
    public function getFallbacks()
    {
        return $this->localeFallbacks;
    }

    /**
     * Set the locale fallback(s).
     *
     * @param string|array $fallback
     *
     * @return $this
     */
    public function setFallbacks($fallback)
    {
        $this->localeFallbacks = (array)$fallback;
        $this->localeSequence = [];
        $this->formatCache = [];
        return $this;
    }


    /**
     * Converts html to plain text.
     *
     * @param string      $html
     *
     * @return string
     **/
    public function plain($html)
    {
        $plain = preg_replace('#<br\s*/?>#iu', "\n", $html);
        $plain = strip_tags($plain);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5);

        return $plain;
    }

    /**
     * Converts plain to html text.
     *
     * @param string      $plain
     *
     * @return string
     **/
    public function html($plain)
    {
        $paragraphs = explode("\n\n", trim($plain));

        $html = '';

        if (count($paragraphs) == 1) {
            return nl2br(trim($paragraphs[0]));
        }

        foreach ($paragraphs as $paragraph) {
            $html .= '<p>'.nl2br(trim($paragraph)).'</p>';
        }

        return $html;
    }

    /**
     * Wraps the passed text in a html (xml) tag.
     *
     * @param string $text
     * @param string $tagName
     *
     * @return string
     **/
    public function tag($text, $tagName)
    {
        return "<$tagName>$text</$tagName>";
    }

    /**
     * @inheritdoc
     *
     * @param string $filter
     * @param array $parameters (optional)
     *
     * @return string
     **/
    public function __call($filter, array $parameters = [])
    {
        if ($this->hasExtension($filter)) {
            return $this->callExtension($filter, $parameters);
        }

        if (method_exists($this, $filter)) {
            return Lambda::callFast([$this, $filter], $parameters);
        }

        if (function_exists($filter)) {
            return Lambda::callFast($filter, $parameters);
        }

        throw new HandlerNotFoundException("Filter '$filter' not found");
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function loadFormat($key)
    {
        if (isset($this->overwrites[$key])) {
            return $this->overwrites[$key];
        }

        foreach ($this->localeSequence() as $locale) {
            $path = "$locale.$key";
            if (isset($this->formats[$path])) {
                return $this->formats[$path];
            }
        }
        throw new KeyNotFoundException("Format for '$key' not found in formats.'");
    }

    /**
     * Calculates the priority for loading formatting keys. (e.g. de_DE, de, en)
     *
     * @return array
     */
    protected function localeSequence()
    {
        if ($this->localeSequence) {
            return $this->localeSequence;
        }

        if ($this->localeFallbacks) {
            $this->localeSequence = $this->localeFallbacks;
        }

        if (!$this->locale) {
            return $this->localeSequence;
        }

        if (!strpos($this->locale, '_') ) {
            array_unshift($this->localeSequence, $this->locale);
            return $this->localeSequence;
        }

        $base = explode('_', $this->locale, 2)[0];

        array_unshift($this->localeSequence, $base);
        array_unshift($this->localeSequence, $this->locale);

        return $this->localeSequence;
    }

    /**
     * Build a format key to get it from config array.
     *
     * @param string $name
     * @param string $verbosity (default: self::SHORT)
     *
     * @return string
     */
    protected function getFormatKey($name, $verbosity=self::SHORT)
    {
        if ($name == self::UNIT || $name == self::MONEY) {
            return "{$this->formatsPrefix}$name";
        }
        return "{$this->formatsPrefix}$name.$verbosity";
    }

    /**
     * Build a symbol key to get it from config array.
     *
     * @param string     $name
     * @param string|int $value
     * @param string     $verbosity (default: self::SHORT)
     *
     * @return string
     */
    protected function getSymbolKey($name, $value, $verbosity=self::SHORT)
    {
        if ($name == self::DECIMAL_MARK || $name == self::THOUSANDS_SEPARATOR) {
            return "{$this->formatsPrefix}number.$name";
        }
        return "{$this->formatsPrefix}$name.$verbosity.$value";
    }

    /**
     * Turns an arbitrary argument into a date.
     *
     * @param mixed $date
     *
     * @return \DateTime
     **/
    protected function toDateTime($date)
    {
        if ($date instanceof DateTime) {
            return $date;
        }

        if (is_numeric($date)) {
            return (new DateTime())->setTimestamp((int) $date);
        }

        // Support for the ancient Zend_Date and others...
        if (is_object($date) && method_exists($date, 'getTimestamp')) {
            return (new DateTime())->setTimestamp($date->getTimestamp());
        }

        if (!is_string($date) && !method_exists($date, '__toString')) {
            $typeName = Helper::typeName($date);
            throw new InvalidArgumentException("No idea how to cast $typeName to DateTime");
        }

        $date = (string) $date;

        if ($dateTime = date_create($date)) {
            return $dateTime;
        }

        throw new InvalidArgumentException("No idea how to cast $date to DateTime");
    }

    /**
     * The date formats has to be parsed before passing them to DateTime. So
     * this method does the parsing and caching.
     *
     * @param string   $type
     * @param string   $verbosity
     * @param DateTime $date
     *
     * @return string
     */
    protected function getDateFormat($type, $verbosity, DateTime $date)
    {
        $numWeekDay = (int)$date->format('w');
        $numWeekDay = $numWeekDay  == 0 ? 7 : $numWeekDay;
        $numMonth =  $date->format('n');

        $cacheKey = "$numWeekDay|$numMonth";

        if (isset($this->dateFormatCache[$type][$verbosity][$cacheKey])) {
            return $this->dateFormatCache[$type][$verbosity][$cacheKey];
        }

        $format = $this->getFormat($type, $verbosity);

        $format = $this->replaceDatePlaceHolders($date, $format);

        $this->dateFormatCache[$type][$verbosity][$cacheKey] = $format;

        return $format;

    }

    /**
     * Parses the weekdays and months inside date or datetime formats.
     *
     * @param DateTime $date
     * @param string   $format
     *
     * @return string
     */
    protected function replaceDatePlaceHolders(DateTime $date, $format)
    {
        $numWeekDay = (int)$date->format('w');
        $numWeekDay = $numWeekDay  == 0 ? 7 : $numWeekDay;
        $numMonth =  $date->format('n');

        // First get the symbols
        $weekDay3 = $this->getSymbol(self::WEEKDAY, "$numWeekDay", self::LONG);
        $weekDay = $this->getSymbol(self::WEEKDAY, "$numWeekDay", self::VERBOSE);
        $month3 = $this->getSymbol(self::MONTH, $numMonth, self::LONG);
        $month = $this->getSymbol(self::MONTH, $numMonth, self::VERBOSE);

        // Escape them to be not be parsed by DateTime
        $weekDay3 = $this->escapeEveryChar($weekDay3);
        $weekDay = $this->escapeEveryChar($weekDay);
        $month3 = $this->escapeEveryChar($month3);
        $month = $this->escapeEveryChar($month);

        // Replace every php date char by the respecting symbol
        $format = $this->replaceUnEscaped('D', $weekDay3, $format);
        $format = $this->replaceUnEscaped('l', $weekDay, $format);
        $format = $this->replaceUnEscaped('M', $month3, $format);
        $format = $this->replaceUnEscaped('F', $month, $format);

        return $format;

    }

    /**
     * Adds a backslash to every char in $string.
     *
     * @param string $string
     *
     * @return string
     */
    protected function escapeEveryChar($string)
    {
        return preg_replace('/[a-zA-Z]/u', '\\\\$0', $string);
    }

    /**
     * Replace any character that is not escaped (by \).
     *
     * @param $search
     * @param $replace
     * @param $subject
     *
     * @return mixed
     */
    protected function replaceUnEscaped($search, $replace, $subject)
    {
        return preg_replace('/([^\\\\]{0,1})([' . $search . '])/u', '$1'.$replace, $subject);
    }
}
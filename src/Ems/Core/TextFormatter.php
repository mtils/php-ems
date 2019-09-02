<?php

namespace Ems\Core;

use DateTimeInterface;
use Ems\Contracts\Core\Localizer;
use Ems\Contracts\Core\PointInTime as PointInTimeContract;
use Ems\Contracts\Core\TextFormatter as FormatterContract;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Support\ProvidesNamedCallableChain;

class TextFormatter implements FormatterContract
{
    use ProvidesNamedCallableChain;

    /**
     * @var \Ems\Contracts\Core\Localizer
     **/
    protected $localizer;

    /**
     * {@inheritdoc}
     *
     * @param mixed        $text
     * @param array|string $filters
     *
     * @return string
     **/
    public function format($text, $filters = [])
    {
        $formatted = $text;

        foreach ($this->buildChain($filters) as $name => $data) {
            $parameters = $data['parameters'];
            array_unshift($parameters, $formatted);

            $formatted = $this->__call($name, $parameters);
        }

        return $formatted;
    }

    /**
     * Converts html to plain text.
     *
     * @param string      $html
     * @param string|bool $nice (optional)
     *
     * @return string
     **/
    public function plain($html, $nice = false)
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
     * @param string|bool $nice  (optional)
     *
     * @return string
     **/
    public function html($plain, $nice = false)
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
     * Wraps the passed text in a html tag.
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
     * Format a date.
     *
     * @param mixed  $date
     * @param string $format (optional)
     *
     * @return string
     **/
    public function date($date, $format = null)
    {
        if ($this->isEmptyDate($date)) {
            return '';
        }

        $isLocalizerFormat = in_array($format, [Localizer::SHORT, Localizer::LONG, Localizer::VERBOSE]);

        if ($format && !$isLocalizerFormat) {
            return $this->toDateTime($date)->format($format);
        }

        if (!$this->localizer) {
            return $this->toDateTime($date)->format('Y-m-d');
        }

        return $this->localizer->date($date, $format ?: Localizer::SHORT);
    }

    /**
     * Format time.
     *
     * @param mixed  $date
     * @param string $format (optional)
     *
     * @return string
     **/
    public function time($date, $format = null)
    {
        if ($this->isEmptyDate($date)) {
            return '';
        }

        $isLocalizerFormat = in_array($format, [Localizer::SHORT, Localizer::LONG, Localizer::VERBOSE]);

        if ($format && !$isLocalizerFormat) {
            return $this->toDateTime($date)->format($format);
        }

        if (!$this->localizer) {
            return $this->toDateTime($date)->format('h:i A');
        }

        return $this->localizer->time($date, $format ?: Localizer::SHORT);
    }

    /**
     * Format date and time.
     *
     * @param mixed  $date
     * @param string $format (optional)
     *
     * @return string
     **/
    public function dateTime($date, $format = null)
    {
        if ($this->isEmptyDate($date)) {
            return '';
        }

        $isLocalizerFormat = in_array($format, [Localizer::SHORT, Localizer::LONG, Localizer::VERBOSE]);

        if ($format && !$isLocalizerFormat) {
            return $this->toDateTime($date)->format($format);
        }

        if (!$this->localizer) {
            return $this->toDateTime($date)->format('Y-m-d H:i:s');
        }

        return $this->localizer->dateTime($date, $format ?: Localizer::SHORT);
    }

    /**
     * Display a nice number.
     *
     * @param string|int|float $number
     * @param int              $decimals (optional)
     *
     * @return string
     **/
    public function number($number, $decimals = 0)
    {
        if (!$this->localizer) {
            return number_format($number, $decimals);
        }

        return $this->localizer->number($number, $decimals);
    }

    /**
     * Display a nice area.
     *
     * @param string|int|float $number
     * @param int              $decimals (optional)
     *
     * @return string
     **/
    public function area($number, $decimals = 0, $unit = null)
    {
        if (!is_numeric($number) || !$number) {
            return '';
        }

        if (!$this->localizer) {
            return $this->number($number, $decimals).' '.($unit ?: 'sqin');
        }

        if (!$unit) {
            $unit = $this->localizer->measuring() == 'imperial' ? 'sqin' : 'm²';
        }

        return $this->localizer->length($number, $decimals, $unit);
    }

    /**
     * Cut a text to n $chars.
     *
     * @param string $text
     * @param int    $decimals (optional)
     *
     * @return string
     **/
    public function chars($string, $chars = 80, $elide = '...', $splitBy = ' ')
    {
        if (mb_strlen($string) <= $chars) {
            return $string;
        }

        $words = explode($splitBy, $string);
        $newString = '';

        foreach ($words as $word) {
            if (mb_strlen("$newString $word") <= $chars) {
                $newString .= " $word";
            } else {
                break;
            }
        }

        return $newString.$elide;
    }

    /**
     * Directly call a filter.
     *
     * @param string $filter
     * @param array  $parameters
     *
     * @return string
     **/
    public function __call($filter, array $params = [])
    {
        if ($this->hasExtension($filter)) {
            return $this->callExtension($filter, $params);
        }

        if (method_exists($this, $filter)) {
            return $this->callFast([$this, $filter], $params);
        }

        if (function_exists($filter)) {
            return $this->callFast($filter, $params);
        }

        throw new HandlerNotFoundException("Filter '$filter' not found");
    }

    /**
     * Set a localizer for some simple formatters.
     *
     * @param \Ems\Contracts\Core\Localizer $localizer
     *
     * @return self
     **/
    public function setLocalizer(Localizer $localizer)
    {
        $this->localizer = $localizer;

        return $this;
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
     * Check if a date param is empty.
     *
     * @param mixed $date
     *
     * @return bool
     **/
    protected function isEmptyDate($date)
    {
        if ($date instanceof PointInTimeContract) {
            return $date->isValid();
        }
        if ($date instanceof DateTimeInterface) {
            return false;
        }
        return $date === null | $date === '' | $date == 0;
    }

    /**
     * Call a method.
     *
     * @param string $name
     * @param array  $params (optional)
     *
     * @return mixed
     **/
    protected function callFast(callable $callable, array $params = [])
    {

        // call_user_func_array seems to be slow
        switch (count($params)) {
            case 1:
                return call_user_func($callable, $params[0]);
            case 2:
                return call_user_func($callable, $params[0], $params[1]);
            case 3:
                return call_user_func($callable, $params[0], $params[1], $params[2]);
            case 4:
                return call_user_func($callable, $params[0], $params[1], $params[2], $params[3]);
            default:
                return call_user_func_array($callable, $params);
        }
    }
}

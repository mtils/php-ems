<?php

namespace Ems\Core;


use DateTimeZone;
use Ems\IntegrationTest;
use Ems\Contracts\Core\Formatter as FormatterContract;
/**
 *  * Created by mtils on 28.11.17 at 06:10.
 **/

class FormatterIntegrationTest extends IntegrationTest
{

    public function test_format_german_number()
    {
        $f = $this->newFormatter('de_DE');

        $this->assertEquals('10.000,34', $f->number(10000.34, 2));
        $this->assertEquals('10.000', $f->number(10000.34, 0));
        $f->setLocale('de_CH');
        $this->assertEquals('10’000.34', $f->number(10000.34, 2));
        $this->assertEquals('10’000', $f->number(10000.34, 0));
    }

    public function test_format_german_date()
    {
        $f = $this->newFormatter('de_DE');

        $date = new \DateTime('2017-11-29 06:08:42 UTC');

        $this->assertEquals('29.11.17', $f->date($date));
        $this->assertEquals('29. November 2017', $f->date($date, Formatter::LONG));
        $this->assertEquals('Mittwoch, 29. November 2017', $f->date($date, Formatter::VERBOSE));

    }

    public function test_format_swiss_date()
    {
        $f = $this->newFormatter('de');

        $date = new \DateTime('2017-01-29 06:08:42 UTC');

        $this->assertEquals('29.01.17', $f->date($date));
        $this->assertEquals('29. Januar 2017', $f->date($date, Formatter::LONG));
        $this->assertEquals('Sonntag, 29. Januar 2017', $f->date($date, Formatter::VERBOSE));

        $f->setLocale('de_AT');

        $this->assertEquals('29.01.17', $f->date($date));
        $this->assertEquals('29. Jänner 2017', $f->date($date, Formatter::LONG));
        $this->assertEquals('Sonntag, 29. Jänner 2017', $f->date($date, Formatter::VERBOSE));

    }

    public function test_format_time()
    {
        $f = $this->newFormatter('de_DE');

        $date = new \DateTime('2017-01-29 06:08:42', new DateTimeZone('CST'));

        $this->assertEquals('06:08', $f->time($date));
        $this->assertEquals('06:08:42 CST', $f->time($date, Formatter::LONG));
        $this->assertEquals('06:08:42 CST', $f->time($date, Formatter::VERBOSE));

    }

    public function test_format_german_datetime()
    {
        $f = $this->newFormatter('de_DE');

        $date = new \DateTime('2017-01-29 06:08:42 CET');

        $this->assertEquals('29.01.17, 06:08', $f->datetime($date));
        $this->assertEquals('29. Januar 2017 um 06:08:42 CET', $f->datetime($date, Formatter::LONG));
        $this->assertEquals('Sonntag, 29. Januar 2017 um 06:08:42 CET', $f->datetime($date, Formatter::VERBOSE));

    }

    public function test_format_german_unit()
    {
        $f = $this->newFormatter('de_DE');

        $number = 6125.78;
        $unit = 'm²';
        $this->assertEquals('6.126 m²', $f->unit($number, $unit, 0));
        $this->assertEquals('6.125,78 m²', $f->unit($number, $unit, 2));
        $this->assertEquals('6.125,8 m²', $f->unit($number, $unit, 1));

        $f->setLocale('en_US');
        $this->assertEquals('6,126 m²', $f->unit($number, $unit, 0));
        $this->assertEquals('6,125.78 m²', $f->unit($number, $unit, 2));
        $this->assertEquals('6,125.8 m²', $f->unit($number, $unit, 1));

    }

    public function test_format_american_money()
    {
        $f = $this->newFormatter('en_US');

        $number = 6125.78;
        $unit = '$';

        $this->assertEquals('6,126 $', $f->money($number, $unit, 0));
        $this->assertEquals('6,125.78 $', $f->money($number, $unit, 2));
        $this->assertEquals('6,125.8 $', $f->money($number, $unit, 1));

        $f->setLocale('de_CH');
        $this->assertEquals('6’126 $', $f->unit($number, $unit, 0));
        $this->assertEquals('6’125.78 $', $f->unit($number, $unit, 2));
        $this->assertEquals('6’125.8 $', $f->unit($number, $unit, 1));

    }

    /**
     * @return Formatter
     */
    protected function newFormatter($locale='')
    {

        $formatter = $this->app(FormatterContract::class);
        return $locale ? $formatter->setLocale($locale) : $formatter;
    }
}
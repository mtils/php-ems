<?php

namespace Ems\Core;

use Ems\Contracts\Core\Formatter as FormatterContract;
use Ems\Contracts\Core\Multilingual;
use Ems\Testing\LoggingCallable;

class FormatterTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(FormatterContract::class, $this->newFormatter());
        $this->assertInstanceOf(Multilingual::class, $this->newFormatter());
    }

    public function test_call_extension()
    {
        $formatter = $this->newFormatter();

        $input = 'foo';
        $result = 'bar';

        $extension = new LoggingCallable(function ($text) use ($result) { return $result; });

        $formatter->extend('foo', $extension);

        $this->assertEquals($result, $formatter->foo($input));
        $this->assertEquals($input, $extension->arg(0));
    }

    public function test_call_method()
    {
        $formatter = $this->newFormatter();

        $input = '<p>Immer &Auml;rger mit Umlauten<br>&Uuml;bel</p>';
        $result = "Immer Ärger mit Umlauten\nÜbel";

        $this->assertEquals($result, $formatter->__call('plain', [$input]));
    }

    public function test_standard_function()
    {
        $formatter = $this->newFormatter();

        $input = ' whitespace ';
        $result = 'whitespace';

        $this->assertEquals($result, $formatter->__call('trim', [$input]));
    }

    /**
     * @expectedException \Ems\Core\Exceptions\HandlerNotFoundException
     **/
    public function test_call_throws_HandlerNotFoundException_if_filter_unknown()
    {
        $formatter = $this->newFormatter();

        $input = ' whitespace ';
        $result = 'whitespace';

        $this->assertEquals($result, $formatter->__call('trimi', [$input]));
    }

    public function test_chain_of_filters_with_parameters()
    {
        $formatter = $this->newFormatter();

        $input = ' whitespace ';
        $filterOutput = 'result';
        $filterParams = 'a,0,22,x';
        $result = "<em>$filterOutput</em>";

        $extension = new LoggingCallable(function ($text) use ($filterOutput) { return $filterOutput; });
        $formatter->extend('foo', $extension);

        $this->assertEquals($result, $formatter->format($input, "trim|foo:$filterParams|tag:em"));

        $this->assertEquals('whitespace', $extension->arg(0));
        $this->assertEquals('a', $extension->arg(1));
        $this->assertEquals('0', $extension->arg(2));
        $this->assertEquals('22', $extension->arg(3));
        $this->assertEquals('x', $extension->arg(4));
    }

    public function test_number_formats_english_number()
    {
        $formats = [
            'en_US.formats.number.decimal_mark' => '.',
            'en_US.formats.number.thousands_separator' => ',',
        ];

        $formatter = $this->newFormatter($formats)->setLocale('en_US');

        $this->assertEquals('11,250.35', $formatter->number(11250.347, 2));
    }

    public function test_number_formats_english_number_different_if_changed_by_setSymbol()
    {
        $formats = [
            'en_US.formats.number.decimal_mark' => '.',
            'en_US.formats.number.thousands_separator' => ',',
        ];

        $formatter = $this->newFormatter($formats)->setLocale('en_US');

        $this->assertEquals('11,250.35', $formatter->number(11250.347, 2));

        $formatter->setSymbol(Formatter::DECIMAL_MARK, '', '|');
        $formatter->setSymbol(Formatter::THOUSANDS_SEPARATOR, '', '=');

        $this->assertEquals('11=250|35', $formatter->number(11250.347, 2));
    }

    public function test_number_formats_german_and_swiss_numbers()
    {
        $formats = [
            'de_CH.formats.number.decimal_mark' => '.',
            'de_CH.formats.number.thousands_separator' => ',',
            'de.formats.number.decimal_mark' => ',',
            'de.formats.number.thousands_separator' => '.'

        ];

        $formatter = $this->newFormatter($formats)->setLocale('de_CH');
        $this->assertEquals('de_CH', $formatter->getLocale());

        $this->assertEquals('11,250.35', $formatter->number(11250.347, 2));

        $formatter = $formatter->forLocale('de_DE');

        $this->assertEquals('11.250,35', $formatter->number(11250.347, 2));

    }

    public function test_weekday_returns_correct_symbol()
    {
        $formats = [
            'en_US.formats.weekday.short.1' => 'M',
            'en_US.formats.weekday.verbose.1' => 'Monday',
            'en.formats.weekday.short.1' => 'm',
            'en.formats.weekday.long.1' => 'mon',
            'en.formats.weekday.verbose.1' => 'monday',
        ];

        $formatter = $this->newFormatter($formats)->setLocale('en_US');

        $this->assertEquals('Monday', $formatter->weekday(1));
        $this->assertEquals('mon', $formatter->weekday(1, Formatter::LONG));
        $this->assertEquals('M', $formatter->weekday(1, Formatter::SHORT));

        $formatter->setSymbol(Formatter::WEEKDAY, 1, 'Mondays', Formatter::VERBOSE);

        $this->assertEquals('Mondays', $formatter->weekday(1));

    }

    public function test_weekday_returns_correct_symbol_with_fallback_lang()
    {
        $formats = [
            'de_DE.formats.weekday.verbose.1' => 'Montag',
            'de.formats.weekday.short.1' => 'm',
            'de.formats.weekday.verbose.1' => 'Montags',
            'en.formats.weekday.long.1' => 'mon',
        ];

        $formatter = $this->newFormatter($formats)->setLocale('de_DE');
        $this->assertSame($formatter, $formatter->setFallbacks('en'));
        $this->assertEquals(['en'], $formatter->getFallbacks());


        $this->assertEquals('Montag', $formatter->weekday(1));
        $this->assertEquals('m', $formatter->weekday(1, Formatter::SHORT));
        $this->assertEquals('mon', $formatter->weekday(1, Formatter::LONG));

        $formatter->setLocale('de');

        $this->assertEquals('Montags', $formatter->weekday(1));

        $formatter->setLocale('');

        $this->assertEquals('mon', $formatter->weekday(1, Formatter::LONG));

    }

    public function test_month_returns_correct_symbol()
    {
        $formats = [
            'en_US.formats.month.short.1'   => 'J',
            'en_US.formats.month.verbose.1' => 'January',
            'en.formats.month.short.1'      => 'j',
            'en.formats.month.long.1'       => 'jan',
            'en.formats.month.verbose.1'    => 'january',
        ];

        $formatter = $this->newFormatter($formats)->setLocale('en_US');

        $this->assertEquals('January', $formatter->month(1));
        $this->assertEquals('jan', $formatter->month(1, Formatter::LONG));
        $this->assertEquals('J', $formatter->month(1, Formatter::SHORT));

        $formatter->setSymbol(Formatter::MONTH, 1, 'First month of year, dude', Formatter::VERBOSE);

        $this->assertEquals('First month of year, dude', $formatter->month(1));

    }

    public function test_date()
    {

        $formatter = $this->newFormatter()->setLocale('de_DE');

        $date  = new \DateTime('2017-11-25 10:58:23');

        $this->assertEquals('25.11.2017', $formatter->date($date));
        $this->assertEquals('25. November 2017', $formatter->date($date, Formatter::LONG));
        $this->assertEquals('Samstag, 25. November 2017', $formatter->date($date, Formatter::VERBOSE));

    }

    public function test_date_with_same_date_char_in_replacement()
    {

        $formatter = $this->newFormatter()->setLocale('de_DE');

        $date  = new \DateTime('2017-11-29 10:58:23');

        $formatter->setFormat(Formatter::DATE, 'l, d.m.Y');

        $this->assertEquals('Mittwoch, 29.11.2017', $formatter->date($date));

    }

    public function test_date_with_overwritten_format()
    {

        $formatter = $this->newFormatter()->setLocale('de_DE');

        $date  = new \DateTime('2017-11-25 10:58:23');

        $this->assertEquals('25.11.2017', $formatter->date($date));
        $this->assertEquals('25. November 2017', $formatter->date($date, Formatter::LONG));
        $this->assertEquals('Samstag, 25. November 2017', $formatter->date($date, Formatter::VERBOSE));

        $formatter->setFormat(Formatter::DATE, '\A\m l, \d\e\n d. F Y', Formatter::VERBOSE);

        $this->assertEquals('Am Samstag, den 25. November 2017', $formatter->date($date, Formatter::VERBOSE));

        // Just test the cache via code coverage...
        $this->assertEquals('Am Samstag, den 25. November 2017', $formatter->date($date, Formatter::VERBOSE));
    }

    public function test_time()
    {

        $formatter = $this->newFormatter()->setLocale('de_DE');

        $date  = new \DateTime('2017-11-25T10:58:23 UTC');

        $this->assertEquals('10:58', $formatter->time($date));
        $this->assertEquals('10:58:23', $formatter->time($date, Formatter::LONG));
        $this->assertEquals('10:58:23 UTC', $formatter->time($date, Formatter::VERBOSE));

    }

    public function test_dateTime()
    {

        $formatter = $this->newFormatter()->setLocale('de_DE');

        $date  = new \DateTime('2017-11-25 10:58:23 UTC');

        $this->assertEquals('25.11.17, 10:58', $formatter->dateTime($date));
        $this->assertEquals('25. November 2017 um 10:58:23', $formatter->dateTime($date, Formatter::LONG));
        $this->assertEquals('Samstag, 25. November 2017 um 10:58:23 UTC', $formatter->dateTime($date, Formatter::VERBOSE));

    }

    public function test_date_with_date_string()
    {

        $formatter = $this->newFormatter()->setLocale('de_DE');

        $dateString = '2017-11-25 10:58:23 UTC';
        $date  = new \DateTime($dateString);

        $this->assertEquals('25.11.2017', $formatter->date($dateString));

    }

    public function test_date_with_unix_timestamp()
    {

        $formatter = $this->newFormatter()->setLocale('de_DE');

        $dateString = '2017-11-25 10:58:23 UTC';
        $date  = new \DateTime($dateString);

        $this->assertEquals('25.11.2017', $formatter->date($date->getTimestamp()));

    }

    public function test_date_with_Custom_Date()
    {

        $formatter = $this->newFormatter()->setLocale('de_DE');

        $dateString = '2017-11-25 10:58:23 UTC';
        $date  = new \DateTime($dateString);
        $customDate = new FormatterTest_CustomDate();
        $customDate->timestamp = $date->getTimestamp();

        $this->assertEquals('25.11.2017', $formatter->date($customDate));

    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_date_with_unsupported_date_throws_exception()
    {

        $formatter = $this->newFormatter()->setLocale('de_DE');

        $formatter->date(new \stdClass());

    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_date_with_unsupported_datestring_throws_exception()
    {

        $formatter = $this->newFormatter()->setLocale('de_DE');

        $formatter->date('Meine Oma fährt im Hühnerstall Motorrad');

    }

    public function test_unit_formats_german_unit()
    {
        $formats = [
            'de.formats.number.decimal_mark' => ',',
            'de.formats.number.thousands_separator' => '.',
            'de_DE.formats.unit' => '{number} {unit}',
            'de.formats.unit' => '{unit} {number}'
        ];

        $formatter = $this->newFormatter($formats)->setLocale('de_DE');

        $this->assertEquals('11.250,35 m²', $formatter->unit(11250.347, 'm²',2));

        $formatter->setLocale('de');

        $this->assertEquals('m² 11.250,35', $formatter->unit(11250.347, 'm²',2));
    }

    public function test_currency_formats_euro()
    {
        $formats = [
            'de.formats.number.decimal_mark' => ',',
            'de.formats.number.thousands_separator' => '.',
            'de_DE.formats.money' => '{currency}{number}',
            'de.formats.money' => '{number} {currency}'
        ];

        $formatter = $this->newFormatter($formats)->setLocale('de_DE');

        $this->assertEquals('€11.250,35', $formatter->money(11250.347, '€',2));

        $formatter->setLocale('de');

        $this->assertEquals('11.250,35 €', $formatter->money(11250.347, '€',2));
    }

    public function test_html_converts_plain_to_html()
    {
        $formatter = $this->newFormatter([]);

        $text = "This is a text\nabout fishes.\n\nFishes live in water.";

        $html = "<p>This is a text<br />\nabout fishes.</p><p>Fishes live in water.</p>";

        $this->assertEquals($html, $formatter->html($text));

        $text = "This is a text\nabout fishes.";

        $html = "This is a text<br />\nabout fishes.";

        $this->assertEquals($html, $formatter->html($text));
    }

    protected function newFormatter($formats=null)
    {
        return new Formatter($formats === null ? $this->formats() : $formats);
    }

    protected function formats()
    {
        return  [
            'de_DE.formats.date.short'     => 'd.m.Y',
            'de_DE.formats.date.verbose'   => 'l, d. F Y',
            'de.formats.date.long'         => 'd. F Y',
            'de.formats.time.short'        => 'H:i',
            'de.formats.time.long'         => 'H:i:s',
            'de.formats.time.verbose'      => 'H:i:s e',
            'de.formats.datetime.short'    => 'd.m.y, H:i',
            'de.formats.datetime.long'     => 'd. F Y \u\m H:i:s',
            'de.formats.datetime.verbose'  => 'l, d. F Y \u\m H:i:s e',
            'de.formats.weekday.long.7'    => "So",
            "de.formats.weekday.long.1"    => "Mo",
            "de.formats.weekday.long.2"    => "Di",
            "de.formats.weekday.long.3"    => "Mi",
            "de.formats.weekday.long.4"    => "Do",
            "de.formats.weekday.long.5"    => "Fr",
            "de.formats.weekday.long.6"    => "Sa",
            "de.formats.weekday.short.7"   => "S",
            "de.formats.weekday.short.1"   => "M",
            "de.formats.weekday.short.2"   => "D",
            "de.formats.weekday.short.3"   => "M",
            "de.formats.weekday.short.4"   => "D",
            "de.formats.weekday.short.5"   => "F",
            "de.formats.weekday.short.6"   => "S",
            "de.formats.weekday.verbose.7" => "Sonntag",
            "de.formats.weekday.verbose.1" => "Montag",
            "de.formats.weekday.verbose.2" => "Dienstag",
            "de.formats.weekday.verbose.3" => "Mittwoch",
            "de.formats.weekday.verbose.4" => "Donnerstag",
            "de.formats.weekday.verbose.5" => "Freitag",
            "de.formats.weekday.verbose.6" => "Samstag",
            "de.formats.month.long.1"      => "Jan",
            "de.formats.month.long.2"      => "Feb",
            "de.formats.month.long.3"      => "Mär",
            "de.formats.month.long.4"      => "Apr",
            "de.formats.month.long.5"      => "Mai",
            "de.formats.month.long.6"      => "Jun",
            "de.formats.month.long.7"      => "Jul",
            "de.formats.month.long.8"      => "Aug",
            "de.formats.month.long.9"      => "Sep",
            "de.formats.month.long.10"     => "Okt",
            "de.formats.month.long.11"     => "Nov",
            "de.formats.month.long.12"     => "Dez",
            "de.formats.month.short.1"      => "J",
            "de.formats.month.short.2"      => "F",
            "de.formats.month.short.3"      => "M",
            "de.formats.month.short.4"      => "A",
            "de.formats.month.short.5"      => "M",
            "de.formats.month.short.6"      => "J",
            "de.formats.month.short.7"      => "J",
            "de.formats.month.short.8"      => "A",
            "de.formats.month.short.9"      => "S",
            "de.formats.month.short.10"     => "O",
            "de.formats.month.short.11"     => "N",
            "de.formats.month.short.12"     => "D",
            "de.formats.month.verbose.1"      => "Januar",
            "de.formats.month.verbose.2"      => "Februar",
            "de.formats.month.verbose.3"      => "März",
            "de.formats.month.verbose.4"      => "April",
            "de.formats.month.verbose.5"      => "Mai",
            "de.formats.month.verbose.6"      => "Juni",
            "de.formats.month.verbose.7"      => "Juli",
            "de.formats.month.verbose.8"      => "August",
            "de.formats.month.verbose.9"      => "September",
            "de.formats.month.verbose.10"     => "Oktober",
            "de.formats.month.verbose.11"     => "November",
            "de.formats.month.verbose.12"     => "Dezember",
        ];
    }
}

class FormatterTest_CustomDate
{
    public $timestamp;

    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
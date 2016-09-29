<?php


namespace Ems\Core\StringConverter;


use Ems\Contracts\Core\StringConverter;


class AsciiStringConverterTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(StringConverter::class, $this->newConverter());
    }

    public function test_can_convert_all_listed_encodings()
    {
        $converter = $this->newConverter();
        foreach ($converter->encodings() as $encoding) {
            $this->assertTrue($converter->canConvert($encoding));
        }
    }

    public function test_convert_leads_to_expected_conversion()
    {
        $converter = $this->newConverter()->setLang('de');

        $tests = [
            'a-great_pig'
                => 'a-great-pig',
            'a great pig'
                => 'a-great-pig',
            'große ölige Ähren grünen für 13€/Stunde '
                => 'grosse-oelige-aehren-gruenen-fuer-13euro-pro-stunde'
        ];

        foreach ($tests as $test=>$awaited) {
            $this->assertEquals($awaited, $converter->convert($test, 'URL-SEGMENT'));
        }
    }

    protected function newConverter()
    {
        return new AsciiStringConverter;
    }

}

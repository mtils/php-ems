<?php


namespace Ems\Core\StringConverter;


use Ems\Contracts\Core\StringConverter;


abstract class AbstractStringConverterTest extends \Ems\TestCase
{

    /**
     * @var string
     **/
    protected $extension;

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
        $converter = $this->newConverter();

        $testString = 'Trälä dös köstet 14 € son Driß';

        foreach ($converter->encodings() as $encoding) {
            $converted = $converter->convert($testString, $encoding);
            $awaited = $this->convert($testString, $encoding);
            $this->assertEquals($awaited, $converted);
        }
    }

    protected function convert($text, $toEncoding, $fromEncoding=null)
    {
        return $text;
    }

    abstract protected function newConverter();

    protected function setUp()
    {
        if (!$this->extension) {
            return;
        }
        if (!extension_loaded($this->extension)) {
            $this->markTestSkipped(
              "The {$this->extension} extension is not available."
            );
        }
    }

}

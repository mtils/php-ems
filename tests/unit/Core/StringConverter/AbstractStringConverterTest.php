<?php

namespace Ems\Core\StringConverter;


use Ems\Contracts\Core\StringConverter;
use function get_class;
use function in_array;
use function strtolower;

abstract class AbstractStringConverterTest extends \Ems\TestCase
{
    /**
     * @var string
     **/
    protected $extension;

    /**
     * @var bool
     **/
    protected $testEveryEncoding = true;

    /**
     * Add some encodings here to skip. Sometimes there are encodings reported by
     * the system but not fully supported to encode/decode
     *
     * @var array
     */
    protected $ignoredEncodings = [];

    protected $minimumEncodings = [
        'UTF-8'         => 'Trälä dös köstet 14 € son Driß',
        'ISO-8859-1'    => 'Trälä dös köstet 14 EUR son Driß',
        'WINDOWS-1251'  => 'А Б В Г Д Е Ж З И Й К Л М Н О П Р С Т У Ф Х Ц Ч Ш Щ Ъ Ы Ь Э Ю Я',
        'WINDOWS-1252'  => 'Trälä dös köstet 14 $ son Driß',
        'SJIS'          => '約30社のカードローンを徹底比較',
        'GB2312'        => '习近平出席意总统举行的欢送仪式',
        'EUC-KR'        => '파워상품',
        'ISO-8859-2'    => 'Dane takie wykorzystywane są przez witrynę tylko gdy użytkownik zdecyduje się na rejestrację w serwisie.',
        'WINDOWS-1250'  => 'Aktuální akční nabídky',
        'EUC-JP'        => 'リンさん父 2年間とても疲れた',
        'GBK'           => '安卓苹果旗舰对决 比完后我扔了果',
        'BIG-5'         => '高效節能UV殺菌 5層過濾清淨機/適用',
        'ISO-8859-15'   => 'Trälä dös köstet 14 € son Driß',
        'WINDOWS-1256'  => 'سعى زين الدين زيدان، مدرب ريال مدريد، لتحقيق نهاية تاريخية للموسم الحالي، الذي عاد خلاله ',
        'ISO-8859-9'    => 'Ankara ve İstanbul İlleri için Aşağıda Belirtilen İlçelere 187 Erkek Çarşı ve Mahalle Bekçisi Alımı Yapılacak'
    ];

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

        $fails = 0;
        $succeeded = 0;

        foreach ($converter->encodings() as $encoding) {

            if (!$this->testEveryEncoding && !isset($this->minimumEncodings[$encoding])) {
                continue;
            }

            $testString = isset($this->minimumEncodings[$encoding]) ? $this->minimumEncodings[$encoding] : 'Just some ascii text';

            if (in_array($encoding, $this->ignoredEncodings)) {
                continue;
            }
            $converted = $converter->convert($testString, $encoding);
            $awaited = strtolower($encoding) == 'pass' ? $testString : $this->convert($testString, $encoding);

            if ($this->testEveryEncoding) {
                $this->assertEquals($awaited, $converted);
                continue;
            }

            if ($awaited != $converted) {
                $fails++;
                continue;
            }

            $succeeded++;
        }

        if (!$this->testEveryEncoding && $fails >= $succeeded) {
            $this->fail("Conversion of $fails failed, which is more a equal $succeeded succeeded");
        }
    }

    protected function convert($text, $toEncoding, $fromEncoding=null)
    {
        return $text;
    }

    /**
     * @return StringConverter
     */
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

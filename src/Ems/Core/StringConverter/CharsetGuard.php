<?php


namespace Ems\Core\StringConverter;

use Ems\Core\Exceptions\InvalidCharsetException;

class CharsetGuard
{

    /**
     * @var string
     **/
    const UTF32_BIG_ENDIAN = 'UTF-32BE';

    /**
     * @var string
     **/
    const UTF32_LITTLE_ENDIAN = 'UTF-32LE';

    /**
     * @var string
     **/
    const UTF16_BIG_ENDIAN = 'UTF-16BE';

    /**
     * @var string
     **/
    const UTF16_LITTLE_ENDIAN = 'UTF-16LE';

    /**
     * @var string
     **/
    const UTF8 = 'UTF-8';

    /**
     * @var MBStringConverter
     */
    protected $converter;

    /**
     * This is filled in __construct()
     *
     * @var array
     **/
    protected $byteOrderMarks = [];

    /**
     * @var array
     **/
    protected $defaultDetectOrder = [
        'UTF-8',
        'ISO-8859-1',
        'Windows-1252',
        'ISO-8859-15',
        'Windows-1251',
        'Windows-1250',
        'SJIS'
    ];

    /**
     * @var array
     **/
    protected $detectOrder;

    public function __construct(MBStringConverter $converter=null)
    {
        $this->converter = $converter ?: new MBStringConverter();
        $this->fillByteOrderMarks();
    }

    /**
     * Try to detect the encoding in $string. Strict is just a hint for
     * mb_detect_encoding, the detector usually tries other ways before
     * asking mb_detect_encoding the way you want it to ask.
     *
     * @param string $string
     * @param array  $detectOrder (optional)
     * @param bool   $strict (default:false)
     *
     * @return string
     **/
    public function detect($string, $detectOrder=[], $strict=false)
    {

        if ($bom = $this->findBOM($string)) {
            return $this->findCharsetByBOM($bom);
        }

        if ($this->isAscii($string)) {
            return 'ASCII';
        }

        if ($this->isUtf8($string)) {
            return 'UTF-8';
        }

        $detectOrder = $detectOrder ?: $this->getDefaultDetectOrder();

        // Passing an array didn't work here, even if doc says...
        return mb_detect_encoding($string, implode(',', $detectOrder), $strict);
    }

    /**
     * Remove th byte order mark from string if setted
     *
     * @param string
     *
     * @return string
     **/
    public function withoutBOM($string)
    {
        if ($bom = $this->findBOM($string)) {
            return substr($string, strlen($bom));
        }
        return $string;
    }

    /**
     * Check if $string is in $encoding
     *
     * @param string $string
     * @param string $encoding
     *
     * @return bool
     **/
    public function isCharset($string, $encoding)
    {
        return strtoupper($this->detect($string, [], true)) == strtoupper($encoding);
    }

    /**
     * Throw an exception if $string is not in $encoding
     *
     * @param string $string
     * @param string $encoding
     **/
    public function forceCharset($string, $encoding)
    {
        if ($this->isCharset($string, $encoding)) {
            return;
        }

        $e = (new InvalidCharsetException($string, $encoding))->useGuard($this);

        throw $e;
    }

    /**
     * Return true if a string contains only chars of the first 128 chars.
     *
     * @param string $string
     *
     * @return bool
     **/
    public function isAscii($string)
    {
        return !preg_match('/[^\x20-\x7f]/', $string);
    }

    /**
     * Return true if a string is utf-8. Returns only true if the
     * string contains special chars.
     *
     * @param string $string
     *
     * @return bool
     *
     * @see https://www.w3.org/International/questions/qa-forms-utf-8.html
     **/
    public function isUtf8($string)
    {
        return (bool)preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);
    }

    /**
     * Return the bom vor $type
     *
     * @param string $type
     *
     * @return string
     *
     * @see self::UTF16_BIG_ENDIAN...
     **/
    public function bom($type)
    {
        return $this->byteOrderMarks[$type];
    }

    /**
     * @return array
     */
    public function getDefaultDetectOrder()
    {
        if ($this->detectOrder === null) {
            $this->detectOrder = $this->buildDetectOrder();
        }
        return $this->detectOrder;
    }

    /**
     * Filters the detect order charsets by the support of the system.
     *
     * @return array
     */
    protected function buildDetectOrder()
    {

        $detectOrder = [];

        foreach ($this->defaultDetectOrder as $i=>$charset) {
            if ($this->converter->canConvert($charset)) {
                $detectOrder[] = $charset;
            }
        }

        return $detectOrder;

    }

    /**
     * Fill the boms with known marks
     **/
    protected function fillByteOrderMarks()
    {
        $this->byteOrderMarks = [
            self::UTF32_BIG_ENDIAN      => chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF),
            self::UTF32_LITTLE_ENDIAN   => chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00),
            self::UTF16_BIG_ENDIAN      => chr(0xFE) . chr(0xFF),
            self::UTF16_LITTLE_ENDIAN   => chr(0xFF) . chr(0xFE),
            self::UTF8                  => chr(0xEF) . chr(0xBB) . chr(0xBF)
        ];
    }
    /**
     * Detect the charset by bom.
     *
     * @param string $bom
     *
     * @return string
     **/
    protected function findCharsetByBOM($bom)
    {
        foreach ($this->byteOrderMarks as $charset=>$knownBom) {
            if ($knownBom === $bom) {
                return $charset;
            }
        }

        return '';

    }

    /**
     * Return the byte order mark of $string. If none return
     * an empty string
     *
     * @param string $string
     *
     * @return string
     **/
    protected function findBOM($string)
    {

        foreach ($this->byteOrderMarks as $name => $bom) {
            if (substr($string, 0, strlen($bom)) === $bom) {
                return $bom;
            }
        }

        return '';
    }

}

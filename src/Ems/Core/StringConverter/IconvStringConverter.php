<?php


namespace Ems\Core\StringConverter;


use Ems\Contracts\Core\StringConverter;
use RuntimeException;

class IconvStringConverter implements StringConverter
{

    /**
     * @var array
     **/
    protected $encodings = [];

    /**
     * @var array
     **/
    protected $encodingLookup = [];

    /**
     * @var array
     **/
    protected $defaultEncoding;

    /**
     * @var bool
     **/
    private $filled = false;

    public function __construct()
    {
        if (!function_exists('iconv_get_encoding')) {
            throw new RuntimeException('mbstring extension not found');
        }
        $this->defaultEncoding = iconv_get_encoding('internal_encoding');
    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @param string $toEncoding
     * @param string $fromEncoding (optional)
     * @return string
     **/
    public function convert($text, $toEncoding, $fromEncoding=null)
    {
        $fromEncoding = $fromEncoding ?: $this->defaultEncoding;
        return @iconv($fromEncoding, $toEncoding, $text);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $encoding
     * @return bool
     **/
    public function canConvert($encoding)
    {
        $this->fillEncodingsOnce();
        return isset($this->encodingLookup[strtoupper($encoding)]);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function encodings()
    {
        $this->fillEncodingsOnce();
        return $this->encodings;
    }

    /**
     * Fill the encodings for faster lookups
     *
     * @return null
     **/
    protected function fillEncodingsOnce()
    {
        if ($this->filled) {
            return;
        }

        foreach ($this->loadEncodings() as $encoding) {

            $encoding = strtoupper($encoding);
            $this->encodings[] = $encoding;
            $this->encodingLookup[$encoding] = true;

        }

        $this->filled = true;
    }

    protected function loadEncodings()
    {
        $shellOutput = [];
        exec('iconv -l', $shellOutput);

        $encodings = [];

        foreach ($shellOutput as $line) {
            $encodings[] = trim($line, '/');
        }

        return $encodings;
    }
}

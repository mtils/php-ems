<?php

namespace Ems\Core\StringConverter;

use Ems\Contracts\Core\StringConverter;
use RuntimeException;
use Ems\Core\Exceptions\NotImplementedException;

class AsciiStringConverter implements StringConverter
{
    /**
     * @var array
     **/
    protected $encodings = [
        'FILENAME',
        'URL-SEGMENT',
    ];

    protected $specialChars = [
        'de' => [
            ' '  => '_',
            '&'  => '-and-',
            '+'  => '-plus-',
            '<'  => '-kleiner-',
            '>'  => '-groesser-',
            '?'  => '-fragezeichen-',
            "'"  => '-anfuehrungszeichen-',
            '"'  => '-anfuehrungszeichen-',
            ':'  => '-doppelpunkt-',
            '|'  => '-pipe-',
            '\\' => '-backslash-',
            '/'  => '-pro-',
            '*'  => '-stern-',
        ],
        'en' => [
            ' '  => '_',
            '&'  => '-and-',
            '+'  => '-plus-',
            '<'  => '-smaller-',
            '>'  => '-greater-',
            '?'  => '-questionmark-',
            "'"  => '-quotation-marks-',
            '"'  => '-quotation-marks-',
            ':'  => '-colon-',
            '|'  => '-pipe-',
            '\\' => '-backslash-',
            '/'  => '-slash-',
            '*'  => '-stern-',
        ],
    ];

    protected $internationalSpecialChars = [
        'ä' => 'ae',
        'Ä' => 'Ae',
        'ö' => 'oe',
        'Ö' => 'Oe',
        'ü' => 'ue',
        'Ü' => 'Ue',
        'ß' => 'ss',
        '€' => 'Euro',
    ];

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

    /**
     * @var string
     **/
    protected $lang = 'en';

    /**
     * @var callable
     **/
    protected $currentLangProvider;

    /**
     * @var \Ems\Contracts\Core\TextConverter
     **/
    protected $internalSpecialCharConverter;

    public function __construct()
    {
        if (!function_exists('mb_internal_encoding')) {
            throw new RuntimeException('mbstring extension not found');
        }
        $this->defaultEncoding = mb_internal_encoding();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @param string $toEncoding
     * @param string $fromEncoding (optional)
     *
     * @return string
     **/
    public function convert($text, $toEncoding, $fromEncoding = null)
    {
        $fromEncoding = $fromEncoding ?: $this->defaultEncoding;

        if ($this->canConvert($fromEncoding)) {
            throw new NotImplementedException('Cant decode from ascii, only encode');
        }

        $whitespace = $toEncoding == 'FILENAME' ? '_' : '-';

        $converted = $this->convertInternationalSpecialChars($text, $fromEncoding);
        $converted = $this->convertSpecialChars($converted, $this->getLang(), $whitespace);
        $converted = $this->purgeRemainingChars($converted);

        if ($toEncoding == 'FILENAME') {
            return $converted;
        }

        return strtolower(str_replace('_', '-', $converted));
    }

    /**
     * {@inheritdoc}
     *
     * @param string $encoding
     *
     * @return bool
     **/
    public function canConvert($encoding)
    {
        return in_array(strtoupper($encoding), $this->encodings);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function encodings()
    {
        return $this->encodings;
    }

    /**
     * Return the current language.
     *
     * @return string
     **/
    public function getLang()
    {
        if ($this->currentLangProvider) {
            return call_user_func($this->currentLangProvider);
        }

        return $this->lang;
    }

    /**
     * Set the current language.
     *
     * @param string $lang
     *
     * @return self
     **/
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Assign a provider who realtime detection of lang.
     *
     * @param callable $provider
     *
     * @return self
     **/
    public function provideCurrentLang(callable $provider)
    {
        $this->currentLangProvider = $provider;

        return $this;
    }

    /**
     * @return \Ems\Contracts\Core\TextConverter
     **/
    public function getInternationalSpecialCharConverter()
    {
        return $this->internalSpecialCharConverter;
    }

    /**
     * Set a converter which can encode to ASCII//TRANSLIT for better umlaut encoding.
     *
     * @param \Ems\Contracts\Core\TextConverter $converter
     *
     * @return self
     **/
    public function setInternationalSpecialCharConverter(TextConverter $converter)
    {
        $this->internalSpecialCharConverter = $converter;

        return $this;
    }

    protected function convertInternationalSpecialChars($text, $fromEncoding)
    {
        if ($this->internalSpecialCharConverter) {
            return $this->internalSpecialCharConverter->convert($text, 'ASCII//TRANSLIT', $fromEncoding);
        }

        return str_replace(
            array_keys($this->internationalSpecialChars),
            array_values($this->internationalSpecialChars),
            $text
        );
    }

    protected function convertSpecialChars($text, $lang, $whitespace = '-')
    {
        $replacements = isset($this->specialChars[$lang]) ? $this->specialChars[$lang] : $this->specialChars['en'];

        $replacements[' '] = $whitespace;

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $text
        );
    }

    protected function purgeRemainingChars($text)
    {
        $text = preg_replace("/[^a-zA-Z0-9\/_|+ -]/u", '', $text);

        return trim($text, '-_');
    }
}

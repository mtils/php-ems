<?php

namespace Ems\Core\StringConverter;

use Ems\Contracts\Core\StringConverter;
use RuntimeException;

class MBStringConverter implements StringConverter
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

        return mb_convert_encoding($text, $toEncoding, $fromEncoding);
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
     * Fill the encodings for faster lookups.
     **/
    protected function fillEncodingsOnce()
    {
        if ($this->filled) {
            return;
        }

        foreach (mb_list_encodings() as $encoding) {
            $encoding = strtoupper($encoding);
            $this->encodings[] = $encoding;
            $this->encodingLookup[$encoding] = true;
        }

        $this->filled = true;
    }
}

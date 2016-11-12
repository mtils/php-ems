<?php

namespace Ems\Contracts\Core;

/**
 * The Textconverter converts text. This doesnt have to be a real
 * existing charset like ISO-8859-1 but also can be FILENAME, URL-PATH,
 * HTML, JSON. The encoding should be uppercase.
 **/
interface StringConverter
{
    /**
     * Convert the passed $text into $toEncoding. Optionally pass an
     * input encoding (defaults to mb_internal_encoding).
     *
     * @param string $text
     * @param string $toEncoding
     * @param string $fromEncoding (optional)
     *
     * @return string
     **/
    public function convert($text, $outEncoding, $inEncoding = null);

    /**
     * Return true if you can convert into (and from) $encoding.
     *
     * @param string $encoding
     *
     * @return bool
     **/
    public function canConvert($encoding);

    /**
     * Return a sequential array of all encoding names.
     *
     * @return array
     **/
    public function encodings();
}

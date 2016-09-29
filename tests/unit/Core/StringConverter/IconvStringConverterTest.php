<?php


namespace Ems\Core\StringConverter;


require_once __DIR__.'/AbstractStringConverterTest.php';


class IconvStringConverterTest extends AbstractStringConverterTest
{

    /**
     * @var string
     **/
    protected $extension = 'iconv';

    protected function convert($text, $toEncoding, $fromEncoding=null)
    {
        $fromEncoding = $fromEncoding ?: 'UTF-8';
        return @iconv($fromEncoding, $toEncoding, $text);
    }

    protected function newConverter()
    {
        return new IconvStringConverter;
    }

}

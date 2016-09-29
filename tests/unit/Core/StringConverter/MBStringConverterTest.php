<?php


namespace Ems\Core\StringConverter;


require_once __DIR__.'/AbstractStringConverterTest.php';


class MBStringConverterTest extends AbstractStringConverterTest
{

    /**
     * @var string
     **/
    protected $extension = 'mbstring';

    protected function convert($text, $toEncoding, $fromEncoding=null)
    {
        return mb_convert_encoding($text, $toEncoding);
    }

    protected function newConverter()
    {
        return new MBStringConverter;
    }

}

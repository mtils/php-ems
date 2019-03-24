<?php

namespace Ems\Core\StringConverter;

use Ems\Core\StringConverterChain;

require_once __DIR__.'/AbstractStringConverterTest.php';


class StringConverterChainTest extends AbstractStringConverterTest
{
    protected $mbString;

    protected $iconvString;

    protected $testEveryEncoding = false;

    protected function convert($text, $toEncoding, $fromEncoding=null)
    {
        try {
            $mbStringConverter = $this->mbStringConverter();
            if ($mbStringConverter->canConvert($toEncoding)) {
                return $mbStringConverter->convert($text, $toEncoding, $fromEncoding);
            }
        } catch (RuntimeException $e) {
        }

        return $this->iconvStringConverter()->convert($text, $toEncoding, $fromEncoding);
    }

    protected function newConverter()
    {
        $chain = new StringConverterChain();

        try {
            $chain->add($this->iconvStringConverter());
        } catch (RuntimeException $e) {
        }

        try {
            $chain->add($this->mbStringConverter());
        } catch (RuntimeException $e) {
        }

        return $chain;
    }

    protected function mbStringConverter()
    {
        if (!$this->mbString) {
            $this->mbString = new MBStringConverter();
        }
        return $this->mbString;
    }

    protected function iconvStringConverter()
    {
        if (!$this->iconvString) {
            $this->iconvString = new IconvStringConverter();
        }
        return $this->iconvString;
    }
}

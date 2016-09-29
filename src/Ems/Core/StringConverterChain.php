<?php


namespace Ems\Core;


use Ems\Contracts\Core\StringConverter;
use Ems\Core\Patterns\TraitOfResponsibility;


class StringConverterChain implements StringConverter
{

    use TraitOfResponsibility;

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
        return $this->findReturningTrueOrFail('canConvert', $toEncoding)
                    ->convert($text, $toEncoding, $fromEncoding);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $encoding
     * @return bool
     **/
    public function canConvert($encoding)
    {
        return (bool)$this->findReturningTrue('canConvert', $encoding);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function encodings()
    {

        $encodings = [];
        $added = [];

        foreach ($this->candidates as $candidate) {

            foreach ($candidate->encodings() as $encoding) {

                if (isset($added[$encoding])) {
                    continue;
                }
                $encodings[] = $encoding;
                $added[$encoding] = true;
            }
        }

        return $encodings;

    }
}

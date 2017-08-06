<?php

namespace Ems\Validation;

use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Validation\ValidationConverter as ConverterContract;
use Ems\Core\Patterns\ExtendableTrait;

class ValidationConverter implements ConverterContract, Extendable
{
    use ExtendableTrait;

    /**
     * {@inheritdoc}
     *
     * @param Validation $validation
     * @param string     $format
     * @param array      $keyTitles (optional)
     * @param array      $customMessages (optional)
     *
     * @return mixed
     **/
    public function convert(Validation $validation, $format, array $keyTitles = [], array $customMessages = [])
    {
        return $this->callExtension($format, [$validation, $format, $keyTitles, $customMessages]);
    }
}

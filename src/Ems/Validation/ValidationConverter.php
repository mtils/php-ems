<?php

namespace Ems\Validation;

use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Validation\ValidationConverter as ConverterContract;
use Ems\Core\Patterns\ExtendableTrait;

class ValidationConverter implements ConverterContract
{
    use ExtendableTrait;

    /**
     * {@inheritdoc}
     *
     * @param Validation $validation
     * @param string     $format
     *
     * @return mixed
     **/
    public function convert(Validation $validation, $format)
    {
        return $this->callExtension($format, [$validation]);
    }
}

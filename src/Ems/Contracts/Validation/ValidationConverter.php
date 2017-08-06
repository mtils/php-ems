<?php

namespace Ems\Contracts\Validation;

/**
 * The ValidationConverter is used to convert validations to
 * message bags, native ValidationExceptions of other frameworks
 * or something different
 **/
interface ValidationConverter
{
    /**
     * Convert a validation into something different
     *
     * @param Validation $validation
     * @param string     $format
     * @param array      $keyTitles (optional)
     * @param array      $customMessages (optional)
     *
     * @return mixed
     **/
    public function convert(Validation $validation, $format, array $keyTitles = [], array $customMessages = []);
}

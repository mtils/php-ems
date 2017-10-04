<?php

namespace Ems\Contracts\Validation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\Extendable;

interface ValidatorFactory
{
    /**
     * Return a validator for $rules. Instantiate it or deliver some predefined
     *
     * @param array             $rules
     * @param AppliesToResource $resource (optional
     *
     * @return Validator
     **/
    public function make(array $rules, AppliesToResource $resource=null);
}

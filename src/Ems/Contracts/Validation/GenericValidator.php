<?php


namespace Ems\Contracts\Validation;

use Ems\Contracts\Core\AppliesToResource;

/**
 * An GenericValidator is a validator which allows to replace its rules,
 **/
interface GenericValidator extends Validator
{
    /**
     * Replace the rules with the passed rules.
     *
     * @param array $rules
     *
     * @return self
     **/
    public function setRules(array $rules);

    /**
     * Set the resource of this validator
     *
     * @param AppliesToResource $resource
     *
     * @return self
     **/
    public function setResource(AppliesToResource $resource);
}

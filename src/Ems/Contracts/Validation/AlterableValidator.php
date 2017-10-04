<?php


namespace Ems\Contracts\Validation;

/**
 * An AlterableValidator is a validator which allows to change its rules,
 * It does not allow to completely replace its rules.
 **/
interface AlterableValidator extends Validator
{
    /**
     * Merge the rules with the default rules.
     *
     * @param array $rules
     *
     * @return self
     **/
    public function mergeRules(array $rules);
}

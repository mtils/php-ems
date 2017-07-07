<?php

namespace Ems\Contracts\Validation;


use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\AppliesToResource;

/**
 * The ems validation system works like the laravel validation with rules.
 * The main reason for that is the great readability of a rules array and
 * that you can change its behaviour easily by changing its rules.
 * But in opposite to laravel its meant to write one validator class for
 * each resource.
 *
 * HasMethodHooks has to provide onBefore('validate'), onAfter('validate')
 * onBefore('parseRules') and onAfter('parseRules') to manipulate the array
 **/
interface Validator extends HasMethodHooks
{
    /**
     * An array of string names rules. Like in laravels validation. The rules
     * are used to map the validator to other validators like a javascript
     * validator.
     *
     * @return array
     **/
    public function rules();

    /**
     * Validates to true or fails by an exception (with unparsed messages)
     *
     * @param array             $input
     * @param AppliesToResource $resource (optional)
     *
     * @return bool (always true)
     **/
    public function validate(array $input, AppliesToResource $resource=null);
}

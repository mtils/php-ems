<?php

namespace Ems\Contracts\Validation;

use ArrayAccess;
use IteratorAggregate;
use Countable;

/**
 * A Validation is the result of a validation process. It is indexed by the keys
 * and has failed rules for every key and its parameters.
 * The validation object can be converted into a messagebag. This is an approach
 * to remove the message handling from the validators and to make the validation
 * make obvious.
 **/
interface Validation extends ArrayAccess, IteratorAggregate, Countable
{
    /**
     * Add a failure to the validation. Pass the key the ruleName and the rule
     * parameters.
     * On a rule like:
     * [ 'login' => 'min:3']
     * It would be: $validation->addFailure('login', 'min', [3]).
     *
     * @param string $key
     * @param string $ruleName
     * @param array  $parameters (optional)
     *
     * @return self
     **/
    public function addFailure($key, $ruleName, array $parameters = []);

    /**
     * Return the complete object data as an array:.
     *
     * [
     *     $key => [
     *         $ruleName => [$param1, $param2]
     *     ]
     * ]
     *
     * @return array
     **/
    public function failures();

    /**
     * Quick access to parameters.
     *
     * @param string $key
     * @param string $ruleName
     *
     * @return array
     **/
    public function parameters($key, $ruleName);

    /**
     * Return the validation rules the validator used
     * to create this validation.
     *
     * @return array
     **/
    public function rules();

    /**
     * Return the validator class that created this validation.
     *
     * @return string
     **/
    public function validatorClass();
}

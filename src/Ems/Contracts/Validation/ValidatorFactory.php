<?php

namespace Ems\Contracts\Validation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\Extendable;

interface ValidatorFactory
{
    /**
     * Create a validator. Optionally pass the ormClass you want to validate by
     * this validator. This allows manipulating rules when extending the app.
     *
     * The ormClass is just a hint. self::get() will never be called to create
     * the validator. If you want to manipulate the rules call:
     * self::get(TheClass::class)->mergeRules($newRules)
     *
     * @param array $rules
     * @param string $ormClass (optional)
     *
     * @return Validator
     */
    public function create(array $rules, string $ormClass='') : Validator;

    /**
     * Get the (registered) validator for $ormClass.
     *
     * @param string $ormClass
     *
     * @return Validator
     */
    public function get(string $ormClass) : Validator;

    /**
     * Shortcut to create a validator and call validate.
     *
     * @param array $rules
     * @param array $input
     * @param object|null $ormObject (optional)
     * @param array $formats (optional)
     *
     * @return array
     */
    public function validate(array $rules, array $input, $ormObject=null, array $formats=[]) : array;
}

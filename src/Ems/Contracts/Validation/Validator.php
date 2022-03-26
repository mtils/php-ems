<?php

namespace Ems\Contracts\Validation;


/**
 * The ems validation system works like the laravel validation with rules.
 * The main reason for that is the great readability of a rules array and
 * that you can change its behaviour easily by changing its rules.
 * But in opposite to create validators on the fly (which you could do here also)
 * you should write validator classes for each of your resources.
 *
 **/
interface Validator
{
    /**
     * Locale parameter for validate method
     */
    const LOCALE = 'locale';

    /**
     * Datetime format parameter for validate method
     */
    const DATETIME_FORMAT = 'datetime_format';

    /**
     * Date format parameter for validate method
     */
    const DATE_FORMAT = 'date_format';

    /**
     * Time format for validate method
     */
    const TIME_FORMAT = 'time_format';

    /**
     * Decimal separator for validation method
     */
    const DECIMAL_SEPARATOR = 'decimal_separator';

    /**
     * An array of string names rules. Like in laravels validation. The rules
     * are used to map the validator to other validators like a javascript
     * validator.
     *
     * @return array
     **/
    public function rules() : array;

    /**
     * Validate and return a sanitized version of $input. Cast dates to
     * datetime, turn foreign keys into objects so that the result can be passed
     * to a repository.
     * Throw anything that is a validation
     *
     * @param array         $input      The input from a request or another input source like import files
     * @param object|null   $ormObject  An orm object that this data will belong to
     * @param array         $formats Pass information how to read the input see self::DATE_FORMAT etc.
     *
     * @return array Return a clean version of the input data that can be processed by a repository
     *
     * @throws ValidationException
     **/
    public function validate(array $input, $ormObject=null, array $formats=[]) : array;

    /**
     * Return the orm class this validator belongs to. Return an empty string if
     * it does not belong to a special class.
     *
     * @return string
     **/
    public function ormClass() : string;

    /**
     * Merge the passed ruled with default rules of this validator. Throw a
     * MergingFailedException if you do not support merging or you do not accept
     * the passed rules for merging.
     *
     * @param array $rules
     * @return Validator
     * @throws MergingFailedException
     */
    public function mergeRules(array $rules) : Validator;

    /**
     * Return true if your validator supports merging of rules.
     *
     * @return bool
     */
    public function canMergeRules() : bool;
}

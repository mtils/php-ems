<?php

namespace Ems\Contracts\Foundation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\HasMethodHooks;

/**
 * The InputNormalizer is a input processing queue. You have three steps:
 * adjust, validate, cast.
 *
 * ADJUST: In adjust you would remove implementation specific stuff like '_method' in
 * symfony or correct user input like minus in credit card numbers. If you use
 * forms with flat structures which should be make structured again do this here.
 * (You would not remove _confirmation fields to not break validation.)
 *
 * VALIDATE: Validate the data using a validator. This steps does not change the
 * input.Just throw an exception if the data is invalid in your validator.
 *
 * CAST: Cast the data to the corresponding types. The end result must be of the
 * type that an internal class like a repository can process the data without
 * further modifications. So instead of strings you find DateTime objects, ranges
 * and arrays in the input after casting it.
 *
 * The purpose of this whole class is to encourage you to develop a standardized
 * way how you handle the input and build your form (field names).
 * If you have for example search objects which accept a range, name every range
 * inputs the same and cast them to a Range object instead of repeating the code
 * in every controller.
 * So usually if you have an api and a normal web app you could use the same
 * controller with different InputNormalizer instances.
 *
 * 
 **/
interface InputNormalizer extends HasMethodHooks
{

    /**
     * Run the whole process (normally adjust, validate, cast)
     *
     * @param array                    $input
     * @param string|AppliesToResource $resource (optional)
     * @param string $locale (optional)
     *
     * @return array
     **/
    public function normalize(array $input, AppliesToResource $resource=null, $locale=null);

    /**
     * Adjust the input so that the validator can validate the input.
     * Remove _method, add - to credit card numbers etc. Pass a chain string
     * to determine the adjusters which should run:
     * @example $normalizer->adjust('to_null|convert_encoding:utf-8|remove_method')
     *
     * Call it with no argument or true to adjust with the predefined adjusters.
     *
     * Call it with false to skip all adjusters.
     * Call it with a \Ems\Contracts\Core\InputProcessor to run it with your own
     * adjuster.
     *
     * @param string|bool|InputProcessor $chain (optional)
     *
     * @return self
     **/
    public function adjust($chain=null);

    /**
     * Validate the input (before storing data). Pass an array of rules to
     * create a validator on the fly.
     * Pass a validator to use your own.
     * Pass true or nothing to validate the data (automatically).
     * Pass false to skip validation.
     *
     * @param array|bool|Ems\Contracts\Validation\Validator $constraint (optional)
     *
     * @return self
     **/
    public function validate($constraint=null);

    /**
     * Cast the input so that the repository can persist the input.
     * Remove anything your repository does not like (_confirmation). Cast
     * values to its real type (DateTime|int|float|bool...).
     *
     * Pass a string to the method to run the corresponding casters
     *
     * @example $normalizer->cast('to_null|convert_encoding:utf-8|remove_method')
     *
     * Call it with no argument or true to adjust with the predefined adjusters.
     *
     * Call it with false to skip all adjusters.
     * Call it with a \Ems\Contracts\Core\InputProcessor to run it with your own
     * caster.
     *
     * @param string|bool|InputProcessor $chain (optional)
     *
     * @return self
     **/
    public function cast($chain=null);

}

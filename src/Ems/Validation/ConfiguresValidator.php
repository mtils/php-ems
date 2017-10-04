<?php


namespace Ems\Validation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Contracts\Validation\GenericValidator as GenericContract;
use Ems\Contracts\Validation\AlterableValidator as AlterableContract;
use Ems\Core\Exceptions\UnsupportedParameterException;


trait ConfiguresValidator
{
    /**
     * Add rules and resource to the validator and return it. This is a helper
     * to not repeat this same lines in every ValidatorFactory.
     *
     * @param ValidatorContract        $validator
     * @param array                    $rules
     * @param string|AppliesToResource $resource (optional)
     *
     * @return Validator
     *
     * @throws UnsupportedParameterException (If validator does not allow to set rules)
     **/
    protected function configureAndReturn(ValidatorContract $validator, array $rules, $resource=null)
    {

        if (!$rules && !$resource) {
            return $validator;
        }

        if ($validator instanceof AlterableContract) {

            $validator = $rules ? $validator->mergeRules($rules) : $validator;

            // Special case of implement both interfaces
            if ($validator instanceof GenericValidator && $resource) {
                $validator->setResource($resource);
            }

            return $validator;

        }

        if (!$validator instanceof GenericContract) {
            $name = get_class($validator);
            throw new UnsupportedParameterException("The validator '$name' does not support setting, merging rules or setting a resource.");
        }

        if ($resource) {
            $validator->setResource($resource);
        }

        return $validator->setRules($rules);

    }
}

<?php


namespace Ems\Validation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Core\Exceptions\UnsupportedParameterException;

use function get_class;


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
     * @return ValidatorContract
     *
     * @throws UnsupportedParameterException (If validator does not allow to set rules)
     **/
    protected function configureAndReturn(ValidatorContract $validator, array $rules, $resource=null)
    {

        if ((!$rules && !$resource)) {
            return $validator;
        }

        if (!$validator->canMergeRules()) {
            $name = get_class($validator);
            throw new UnsupportedParameterException("The validator '$name' does not support merging rules.");
        }

        $validator = $rules ? $validator->mergeRules($rules) : $validator;

        // Special case of implement both interfaces
        if ($validator instanceof GenericValidator && $resource) {
            $validator->setOrmClass(get_class($resource));
        }

        return $validator;

    }
}

<?php


namespace Ems\Validation\Illuminate;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Validation\ConfiguresValidator;

/**
 * This class creates a validator out of rules.
 **/
class ValidatorFactory implements ValidatorFactoryContract, SupportsCustomFactory
{
    use CustomFactorySupport;
    use ConfiguresValidator;

    /**
     * Create a validator for $rules and $resource
     *
     * @param array             $rules
     * @param AppliesToResource $resource (optional)
     *
     * @return \Ems\Contracts\Validation\Validator|null
     **/
    public function make(array $rules, AppliesToResource $resource=null)
    {

        if (!$rules || $resource) {
            return null;
        }

        /** @var GenericValidator $validator */
        $validator = $this->createObject(GenericValidator::class, [
            'rules' => $rules
        ]);
        return $this->configureAndReturn($validator, $rules, $resource);
    }

}

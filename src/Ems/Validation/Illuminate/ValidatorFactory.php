<?php


namespace Ems\Validation\Illuminate;

use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Validation\Validator;
use Ems\Core\Support\CustomFactorySupport;
use ReflectionException;

/**
 * This class creates a validator out of rules.
 **/
class ValidatorFactory implements SupportsCustomFactory
{
    use CustomFactorySupport;

    /**
     * Create a validator for $rules and $ormClass
     *
     * @param array $rules
     * @param string $ormClass (optional)
     *
     * @return ValidatorContract
     *
     * @throws ReflectionException
     */
    public function validator(array $rules, string $ormClass='') : ValidatorContract
    {

        /** @var Validator $validator */
        $validator = $this->createObject(Validator::class, [
            'rules'         => $rules,
            'ormClass'      => $ormClass,
            'baseValidator' => $this->createObject(IlluminateBaseValidator::class)
        ]);

        if ($validator->canMergeRules()) {
            $validator->mergeRules($rules);
        }

        return $validator;
    }

}

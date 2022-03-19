<?php

namespace Ems\Validation\Illuminate;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\Entity;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Validation\Validation;
use Ems\Core\Collections\NestedArray;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Validation\Validator as AbstractValidator;
use Illuminate\Contracts\Validation\Factory as IlluminateFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;


abstract class Validator extends AbstractValidator implements AppliesToResource, HasMethodHooks
{
    /**
     * @var IlluminateFactory
     **/
    protected $validatorFactory;

    /**
     * @var \Illuminate\Contracts\Validation\Validator
     **/
    protected $illuminateValidator;

    /**
     * Perform all validation by the the base validator
     *
     * @param Validation    $validation
     * @param array         $input
     * @param array         $baseRules
     * @param object|null   $ormObject (optional)
     * @param array         $formats (optional)
     *
     * @return array
     **/
    protected function validateByBaseValidator(Validation $validation, array $input, array $baseRules, $ormObject = null, array $formats=[]) : array
    {
        $laravelRules = $this->toLaravelRules($baseRules);

        $illuminateValidator = $this->buildValidator($input, $laravelRules);

        if (!$illuminateValidator->fails()) {
            return $input;
        }

        foreach ($illuminateValidator->failed() as $key=>$fails) {
            foreach ($fails as $ruleName=>$parameters) {
                $validation->addFailure($key, Type::snake_case($ruleName), $parameters);
            }
        }
        return $input;
    }

    /**
     * {@inheritdoc}
     *
     * @param array         $rules
     * @param array         $input
     * @param object|null   $ormObject (optional)
     * @param array         $formats (optional)
     *
     * @return array
     **/
    protected function prepareRulesForValidation(array $rules, array $input, $ormObject=null, array $formats=[]) : array
    {

        $preparedRules = [];
        $rules = parent::prepareRulesForValidation($rules, $input, $ormObject, $formats);

//        $dateConstraints = ['after', 'before', 'date'];

        // Flatify to allow nested checks
        $input = NestedArray::flat($input);

        foreach ($rules as $key=>$constraints) {

            $preparedRules[$key] = [];

            foreach ($constraints as $constraint=>$parameters) {

//                 if (in_array($constraint, $dateConstraints)) {
                    // Handle that later, we need complete localized formats
                    // to make this work (next EMS version)

//                 }

                if ($constraint == 'unique') {
                    $preparedRules[$key][$constraint] = $this->parametersOfUniqueConstraint($key, $parameters, $ormObject);
                    continue;
                }

                // required on an existing model should mean:
                // If the key isset, it should not contain any empty values
                // if not, the request is valid.
                // So just remove it if it shouldn't be updated
                if ($ormObject instanceof Entity && !$ormObject->isNew() && $constraint == 'required') {
                    if (!array_key_exists($key, $input)) {
                        continue;
                    }
                }

                $preparedRules[$key][$constraint] = $parameters;


            }

        }

        return $preparedRules;

    }

    /**
     * Calculate the unique parameters for the uniqueParameters.
     *
     * @param string        $key
     * @param array         $originalParameters
     * @param object|null   $ormObject (optional)
     *
     * @return array
     **/
    protected function parametersOfUniqueConstraint(string $key, array $originalParameters, $ormObject=null) : array
    {
        if (!$ormObject instanceof Entity) {
            return $originalParameters;
        }

        if (!$ormObject instanceof EloquentModel) {
            return $originalParameters;
        }

        // No support for nested keys currently
        if (strpos($key, '.') !== false) {
            return $originalParameters;
        }

        $table = $ormObject->getTable();
        $uniqueKey = $key;

        if ($ormObject->isNew()) {
            return [$table, $uniqueKey];
        }

        $id = $ormObject->getId();
        $primaryKey = $ormObject->getKeyName();

        return [$table, $uniqueKey, $id, $primaryKey];
    }

    /**
     * Assign the laravel factory. This has been removed from the
     * constructor to allow your own constructor.
     *
     * @param IlluminateFactory $validatorFactory
     *
     * @return self
     **/
    public function injectIlluminateFactory(IlluminateFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
        return $this;
    }

    /**
     * @param array $input
     * @param array $rules
     *
     * @return \Illuminate\Contracts\Validation\Validator
     **/
    protected function buildValidator(array $input, array $rules)
    {
        if (!$this->validatorFactory) {
            throw new UnConfiguredException("You have to assign the Illuminate Validation\Factory or this validator does not work.");
        }
        return $this->validatorFactory->make($input, $rules);
    }

    /**
     * Convert the ems rules into the laravel format
     *
     * @param array $parsedRules
     *
     * @return array
     **/
    protected function toLaravelRules(array $parsedRules)
    {
        $laravelRules = [];

        foreach ($parsedRules as $key=>$keyRules) {
            $laravelRules[$key] = [];

            foreach ($keyRules as $ruleName=>$parameters) {
                $separator = $parameters ? ':' : '';
                $laravelRules[$key][] = $ruleName.$separator.implode(',', $parameters);
            }
        }

        return $laravelRules;
    }

}

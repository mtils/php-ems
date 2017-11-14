<?php

namespace Ems\Validation\Illuminate;

use Ems\Contracts\Validation\Validator as EmsValidatorContract;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\Entity;
use Illuminate\Contracts\Validation\Factory as IlluminateFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Ems\Contracts\Validation\Validation;
use Ems\Validation\Validator as AbstractValidator;
use Ems\Validation\ValidationException;
use Ems\Core\Helper;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Lambda;
use Ems\Core\Collections\NestedArray;


abstract class Validator extends AbstractValidator
{
    /**
     * @var IlluminateFactory
     **/
    protected $validatorFactory;

    /**
     * @var Illuminate\Contracts\Validation\Validator
     **/
    protected $illuminateValidator;

    /**
     * Perform all validation by the the base validator
     *
     * @param Validation        $validation
     * @param array             $input
     * @param array             $baseRules
     * @param AppliesToResource $resource (optional)
     * @param string            $locale (optional)
     **/
    protected function validateByBaseValidator(Validation $validation, array $input, array $baseRules, AppliesToResource $resource=null, $locale=null)
    {
        $laravelRules = $this->toLaravelRules($baseRules);

        $illuminateValidator = $this->buildValidator($input, $laravelRules);

        if (!$illuminateValidator->fails()) {
            return;
        }

        foreach ($illuminateValidator->failed() as $key=>$fails) {
            foreach ($fails as $ruleName=>$parameters) {
                $validation->addFailure($key, Helper::snake_case($ruleName), $parameters);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param array             $rules
     * @param array             $input
     * @param AppliesToResource $resource (optional)
     * @param string            $locale (optional)
     *
     * @return array
     **/
    protected function prepareRulesForValidation(array $rules, array $input, AppliesToResource $resource=null, $locale=null)
    {

        $preparedRules = [];
        $rules = parent::prepareRulesForValidation($rules, $input, $resource, $locale);

        $dateConstraints = ['after', 'before', 'date'];

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
                    $preparedRules[$key][$constraint] = $this->parametersOfUniqueContraint($key, $parameters, $resource);
                    continue;
                }

                // required on an existing model should mean:
                // If the key isset, it should not contain any empty values
                // if not, the request is valid.
                // So just remove it if it shouldnt be updated
                if ($resource instanceof Entity && !$resource->isNew() && $constraint == 'required') {
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
     * @param string            $key
     * @param array             $originalParameters
     * @param AppliesToResource $resource (optional)
     **/
    protected function parametersOfUniqueContraint($key, array $originalParameters, AppliesToResource $resource=null)
    {
        if (!$resource instanceof Entity) {
            return $originalParameters;
        }

        if (!$resource instanceof EloquentModel) {
            return $originalParameters;
        }

        // No support for nested keys currently
        if (strpos($key, '.') !== false) {
            return $originalParameters;
        }

        $table = $resource->getTable();
        $uniqueKey = $key;

        if ($resource->isNew()) {
            return [$table, $uniqueKey];
        }

        $id = $resource->getId();
        $primaryKey = $resource->getKeyName();

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
                $seperator = $parameters ? ':' : '';
                $laravelRules[$key][] = $ruleName.$seperator.implode(',', $parameters);
            }
        }

        return $laravelRules;
    }

}

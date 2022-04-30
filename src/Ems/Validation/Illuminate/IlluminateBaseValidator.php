<?php
/**
 *  * Created by mtils on 03.04.2022 at 12:27.
 **/

namespace Ems\Validation\Illuminate;

use Ems\Contracts\Core\Entity;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Validation\Validation;
use Ems\Core\Collections\NestedArray;
use Illuminate\Contracts\Validation\Factory as IlluminateFactory;
use Illuminate\Contracts\Validation\Validator as IlluminateValidator;
use Illuminate\Database\Eloquent\Model as EloquentModel;

use function array_key_exists;
use function implode;
use function strpos;

class IlluminateBaseValidator
{
    /**
     * @var IlluminateFactory
     */
    protected $factory;

    /**
     * @param IlluminateFactory $factory
     */
    public function __construct(IlluminateFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Perform all validation by the the base validator
     *
     * @param Validation    $validation
     * @param array         $input
     * @param array         $rules
     * @param object|null   $ormObject (optional)
     * @param array         $formats (optional)
     *
     * @return array
     **/
    protected function validate(Validation $validation, array $input, array $rules, $ormObject = null, array $formats=[]) : array
    {

        $rules = $this->prepareRules($rules, $input, $ormObject, $formats);

        $laravelRules = $this->toLaravelRules($rules);

        $illuminateValidator = $this->makeValidator($input, $laravelRules);

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
     * Alias for self::validate() for simple assignment in Validator.
     *
     * @param Validation    $validation
     * @param array         $input
     * @param array         $rules
     * @param object|null   $ormObject (optional)
     * @param array         $formats (optional)
     *
     * @return array
     **/
    public function __invoke(Validation $validation, array $input, array $rules, $ormObject = null, array $formats=[]) : array
    {
        return $this->validate($validation, $input, $rules, $ormObject, $formats);
    }

    protected function makeValidator(array $input, array $rules) : IlluminateValidator
    {
        return $this->factory->make($input, $rules);
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
    protected function prepareRules(array $rules, array $input, $ormObject=null, array $formats=[]) : array
    {

        $preparedRules = [];

        // Flatify to allow nested checks
        $input = NestedArray::flat($input);

        foreach ($rules as $key=>$constraints) {

            $preparedRules[$key] = [];

            foreach ($constraints as $constraint=>$parameters) {

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
     * Convert the ems rules into the laravel format
     *
     * @param array $parsedRules
     *
     * @return array
     **/
    protected function toLaravelRules(array $parsedRules) : array
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
}
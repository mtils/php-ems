<?php

namespace Ems\Validation\Illuminate;

use Ems\Contracts\Validation\Validator as EmsValidatorContract;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Core\Patterns\HookableTrait;
use Illuminate\Contracts\Validation\Factory as IlluminateFactory;
use Ems\Contracts\Validation\Validation;
use Ems\Validation\ValidationException;
use Ems\Validation\RuleParseMethods;
use Ems\Core\Helper;
use Ems\Core\Exceptions\KeyNotFoundException;
use ReflectionMethod;

class Validator implements EmsValidatorContract
{
    use HookableTrait;
    use RuleParseMethods;

    /**
     * Put your rules here to extend this validator
     *
     * @var array
     **/
    protected $rules = [];

    /**
     * Here the extended (added by hook) rules
     *
     * @var array
     **/
    protected $extendedRules = [];

    /**
     * @var array
     **/
    protected $parsedRules;

    /**
     * @var array
     **/
    protected $ownValidationMethods;

    /**
     * @var IlluminateFactory
     **/
    protected $validatorFactory;

    /**
     * @var Illuminate\Contracts\Validation\Validator
     **/
    protected $illuminateValidator;

    /**
     * @param IlluminateFactory $illuminateFactory
     **/
    public function __construct(IlluminateFactory $illuminateFactory)
    {
        $this->validatorFactory = $illuminateFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public final function rules()
    {
        if ($this->parsedRules !== null) {
            return $this->parsedRules;
        }

        $rules = $this->rules;

        $this->callBeforeListeners('parseRules', [&$rules]);
        $rules = $this->parseRules($rules);
        $this->callAfterListeners('parseRules', [&$rules]);

        $this->parsedRules = $rules;

        return $this->parsedRules;
    }

    /**
     * Validates to true or fails by an exception (with unparsed messages)
     *
     * @param array             $input
     * @param AppliesToResource $resource (optional)
     *
     * @return bool (always true)
     **/
    public final function validate(array $input, AppliesToResource $resource=null)
    {
        $rules = $this->rules();

        $this->callBeforeListeners('validate', [$input, $rules, $resource]);


        $validation = $this->performValidation($input, $rules, $resource);

        $this->callAfterListeners('validate', [$input, $rules, $resource, $validation]);

        if (count($validation)) {
            throw $validation;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function methodHooks()
    {
        return ['validate', 'parseRules'];
    }

    /**
     * @param array $rules
     *
     * @return self
     **/
    public function setRules(array $rules)
    {
        $this->rules = $rules;
        $this->extendedRules = [];
        $this->parsedRules = null;
        return $this;
    }

    /**
     * Overwrite this method to do some processing on the rules before
     * using them
     *
     * @param array $rules
     *
     * @return array
     **/
    protected function parseRules(array $rules)
    {
        $parsedRules = [];

        foreach ($rules as $key=>$keyRules) {
            $keyRuleArray = $this->explodeRules($keyRules);

            $parsedRules[$key] = [];

            foreach ($keyRuleArray as $keyRule) {
                list($ruleName, $parameters) = $this->ruleAndParameters($keyRule);
                $parsedRules[$key][$ruleName] = $parameters;
            }
        }

        return $parsedRules;
    }

    /**
     * @param array $input
     * @param array $rules
     *
     * @return \Illuminate\Contracts\Validation\Validator
     **/
    protected function buildValidator(array $input, array $rules)
    {
        return $this->validatorFactory->make($input, $rules);
    }

    /**
     * @param array             $input
     * @param array             $parsedRules
     * @param AppliesToResource $resource (optional)
     *
     * @return Validation
     **/
    protected function performValidation(array $input, array $parsedRules, AppliesToResource $resource=null)
    {

        // First split rules into base rules and the custom rules of this class
        list($baseRules, $ownRules) = $this->toBaseAndOwnRules($parsedRules);

        $validation = new ValidationException();
        $validation->setRules($parsedRules);

        if ($baseRules) {
            $this->validateByLaravel($validation, $input, $baseRules, $resource);
        }

        if ($ownRules) {
            $this->validateByOwnMethods($validation, $input, $ownRules, $resource);
        }
        return $validation;
    }

    /**
     * Perform all validation by the the base validator
     *
     * @param Validation        $validation
     * @param array             $input
     * @param array             $baseRules
     * @param AppliesToResource $resource (optional)
     **/
    protected function validateByLaravel(Validation $validation, array $input, array $baseRules, AppliesToResource $resource=null)
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
     * Perform all validation by the custom methods of this class
     *
     * @param Validation        $validation
     * @param array             $input
     * @param array             $ownRules
     * @param AppliesToResource $resource (optional)
     **/
    protected function validateByOwnMethods(Validation $validation, array $input, array $ownRules, AppliesToResource $resource=null)
    {
        foreach ($ownRules as $key=>$keyRules) {
            $vars = [
                'input'    => $input,
                'key'      => $key,
                'value'    => Helper::value($input, $key),
                'resource' => $resource
            ];

            foreach ($keyRules as $ruleName=>$parameters) {
                if ($this->validateByOwnMethod($ruleName, $vars, $parameters)) {
                    continue;
                }

                $validation->addFailure($key, $ruleName, $parameters);
            }
        }
    }

    /**
     * Split the rules into rules of this class and other rules.
     *
     * @param array
     *
     * @return array
     **/
    protected function toBaseAndOwnRules(array $parsedRules)
    {
        $ownRules = [];
        $baseRules = [];

        $own = $this->getOwnValidationMethods();

        foreach ($parsedRules as $key=>$keyRules) {
            foreach ($keyRules as $ruleName=>$parameters) {
                if (isset($own[$ruleName])) {
                    (array)$ownRules[$key][$ruleName] = $parameters;
                    continue;
                }

                (array)$baseRules[$key][$ruleName] = $parameters;
            }
        }

        return [$baseRules, $ownRules];
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

    /**
     * Perform the validation by an own validator method.
     *
     * @param string $ruleName
     * @param array  $vars       The validation variables (input, value, key, resource)
     * @param array  $parameters The rule parameters (in rules array)
     *
     * @return bool
     **/
    protected function validateByOwnMethod($ruleName, array $vars, array $parameters)
    {
        $own = $this->getOwnValidationMethods()[$ruleName];

        $method = $own['method'];

        $methodParams = [];

        foreach ($own['parameters'] as $paramName=>$isOptional) {
            if (isset($vars[$paramName])) {
                $methodParams[] = $vars[$paramName];
                continue;
            }

            $methodParams[] = array_shift($parameters);
        }

        return call_user_func_array([$this, $method], $methodParams);
    }

    /**
     * Collect all methods which should be used as validation rules. Also
     * information about parameter names and if they are optional
     *
     * @return array
     **/
    protected function getOwnValidationMethods()
    {
        if ($this->ownValidationMethods !== null) {
            return $this->ownValidationMethods;
        }

        $this->ownValidationMethods = [];

        foreach (get_class_methods($this) as $method) {
            if (!$this->isRuleMethod($method)) {
                continue;
            }

            $methodData = [
                'method'     => $method,
                'rule_name'  => $this->methodNameToRule($method),
                'parameters' => []
            ];

            $reflection = new ReflectionMethod($this, $method);

            foreach ($reflection->getParameters() as $parameter) {
                $methodData['parameters'][$parameter->getName()] = $parameter->isOptional();
            }

            $this->ownValidationMethods[$methodData['rule_name']] = $methodData;
        }

        return $this->ownValidationMethods;
    }

    /**
     * Test if a method name is a validation rule method
     *
     * @param string $methodName
     *
     * @return bool
     **/
    protected function isRuleMethod($methodName)
    {
        if (in_array($methodName, ['validateByLaravel', 'validateByOwnMethods'])) {
            return false;
        }
        return ($methodName != 'validate') && Helper::startsWith($methodName, 'validate');
    }

    /**
     * Converts a validation method name to a rule name
     *
     * @param string $methodName
     *
     * @return string
     **/
    protected function methodNameToRule($methodName)
    {
        return Helper::snake_case(substr($methodName, 8));
    }

}

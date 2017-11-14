<?php


namespace Ems\Validation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\HasInjectMethods;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Contracts\Validation\GenericValidator as GenericValidatorContract;
use Ems\Contracts\Validation\ResourceRuleDetector;
use Ems\Contracts\Validation\Validation;
use Ems\Contracts\XType\TypeProvider;
use Ems\Contracts\XType\XType;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Helper;
use Ems\Contracts\Core\Entity;
use Ems\Core\Lambda;
use Ems\Core\Collections\NestedArray;
use Ems\Expression\ConstraintParsingMethods;
use Ems\XType\SequenceType;


abstract class Validator implements ValidatorContract, HasInjectMethods
{
    use HookableTrait;
    use ConstraintParsingMethods;

    /**
     * @var array
     **/
    protected $rules = [];

    /**
     * Put the relations into that array which are handled by this validator.
     * This is used for automatic rule detection.
     *
     * @example ['address', 'address.country']
     *
     * @var array
     **/
    protected $relations = [];

    /**
     * @var array
     **/
    protected $parsedRules;

    /**
     * @var array
     **/
    protected $ownValidationMethods;

    /**
     * @var ResourceRuleDetector
     **/
    protected $ruleDetector;

    /**
     * @var TypeProvider
     **/
    protected $typeProvider;

    /**
     * Perform validation by the the base validator. Reimplement this method
     * to use it with your favourite base validator.
     *
     * @param Validation        $validation
     * @param array             $input
     * @param array             $baseRules
     * @param AppliesToResource $resource (optional)
     * @param string            $locale (optional)
     **/
    abstract protected function validateByBaseValidator(Validation $validation, array $input, array $baseRules, AppliesToResource $resource=null, $locale=null);

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function rules()
    {
        if ($this->parsedRules !== null) {
            return $this->parsedRules;
        }

        $rules = $this->buildRules();

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
     * @param string            $locale (optional)
     *
     * @return bool (always true)
     **/
    public function validate(array $input, AppliesToResource $resource=null, $locale=null)
    {

        // If a generic validator didnt have a resource until here, assign it
        if (!$this->rules && $this instanceof GenericValidatorContract && !$this->resource()) {
            $this->setResource($resource);
        }

        $rules = $this->prepareRulesForValidation($this->rules(), $input, $resource, $locale);

        $this->callBeforeListeners('validate', [$input, $rules, $resource, $locale]);

        $validation = $this->performValidation($input, $rules, $resource, $locale);

        $this->callAfterListeners('validate', [$input, $rules, $resource, $validation, $locale]);

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
        return ['parseRules', 'validate'];
    }

    /**
     * @return string
     **/
    public function resourceName()
    {
        if ($resource = $this->resource()) {
            return $resource->resourceName();
        }
        throw new UnConfiguredException("No Resource provided to get the resource name");
    }

    /**
     * Set a rule detector to easily detect the rules. Right now only
     * XTypeProviderValidatorFactory is supported.
     *
     * @param ResourceRuleDetector $detector
     *
     * @return self
     **/
    public function injectRuleDetector(ResourceRuleDetector $detector)
    {
        $this->ruleDetector = $detector;
        return $this;
    }

    /**
     * Set a type provider for better automatic rule detection an filtering.
     *
     * @param TypeProvider $provider
     *
     * @return self
     **/
    public function injectTypeProvider(TypeProvider $provider)
    {
        $this->typeProvider = $provider;
        return $this;
    }

    /**
     * A validation rule. It fails if the key was found in the input.
     *
     * @param array $input
     * @param string $key
     *
     * @return bool
     **/
    protected function validateForbidden($input, $key)
    {
        $flat = NestedArray::flat($input);
        return !array_key_exists($key, $flat);
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
        return $this->parseConstraints($rules);
    }

    /**
     * Try to detect rules for the resource of this validator.
     *
     * @param AppliesToResource $resource
     *
     * @return array
     **/
    protected function detectRules(AppliesToResource $resource)
    {

        if (!$this->ruleDetector) {
            throw new UnConfiguredException("You have to assign a rule detector to detect rules");
        }

        return $this->ruleDetector->detectRules($resource, $this->relations);
    }

    /**
     * Apply some filtering on the detected rules. If they are provided by
     * a TypeProvider they contain really all keys, also readonly
     *
     * @param array $rules
     * @param AppliesToResource $resource
     *
     * @return array
     **/
    protected function filterDetectedRules(array $rules, AppliesToResource $resource)
    {

        if (!$this->typeProvider) {
            return $rules;
        }

        $filteredRules = [];

        foreach ($rules as $key=>$rule) {

            if (!$type = $this->xType($key)) {
                continue;
            }

            if ($this->shouldRemoveRules($key, $type)) {
                continue;
            }

            if ($type->readonly) {
                $filteredRules[$key] = 'forbidden';
                continue;
            }

            $filteredRules[$key] = $rule;
        }

        return $filteredRules;
    }

    /**
     * Return true if rules for a key should be removed from the detected rules
     *
     * @param string $key
     * @param XType  $type
     *
     * @return bool
     **/
    protected function shouldRemoveRules($key, XType $type)
    {

        if ($type instanceof SequenceType) {
            return true;
        }

        if ($type->isComplex()) {
            return true;
        }

        return false;
    }

    /**
     * This method is called just to have a hook to build initial rules.
     *
     * @return array
     **/
    protected function buildRules()
    {
        if ($this->rules) {
            return $this->rules;
        }

        if ($resource = $this->resource()) {
            $this->rules = $this->filterDetectedRules($this->detectRules($resource), $resource);
        }

        return $this->rules;
    }

    /**
     * Here is the point where you should change validations caused by the
     * state of the resource (unique keys, exists,...) and depending on the
     * current locale.
     * It allows you to have simple rule expressions like date and then add
     * the localized date format later.
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

        $prepared = [];

        // Flatify to make nested checks more easy
        $flatInput = NestedArray::flat($input);

        $passedRelations = $this->collectRelations($flatInput);

        $entityExists = ($resource instanceof Entity) && !$resource->isNew();


        foreach ($rules as $key=>$constraints) {

            $keyRules = [];

            foreach ($constraints as $constraint=>$parameters) {

                // The parent segment of the nested key
                $relation = $this->getRelationName($key);

                $relationNotInInput = (bool)$relation && !in_array($relation, $passedRelations);

                // If this is an optional relation, and no key was passed, skip it
                if ($relationNotInInput && $this->isOptionalRelation($relation)) {
                    continue;
                }

                if (!$entityExists) {
                    $keyRules[$constraint] = $parameters;
                    continue;
                }

                // Not passed values should have no rules if the entity exists
                // TODO: nested models are not supported right now
                if (!$relation && !array_key_exists($key, $flatInput)) {
                    continue;
                }

                // If this is no related key just add it
                if (!$relation) {
                    $keyRules[$constraint] = $parameters;
                    continue;
                }

                // If the entity exists we can skip every not passed relation
                // and remove all its rules
                if ($relationNotInInput) {
                    continue;
                }

                $keyRules[$constraint] = $parameters;

            }

            if (count($keyRules)) {
                $prepared[$key] = $keyRules;
            }
        }

        return $prepared;
    }

    /**
     * Here you can determine if a relation is optional. This means that if
     * no key of that relation was passed in the request we can ignore all other
     * rules of that relation.
     *
     * @param string $relation
     *
     * @return bool
     **/
    protected function isOptionalRelation($relation)
    {
        return false;
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

            $this->ownValidationMethods[$this->methodNameToRule($method)] = $method;
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
        if (in_array($methodName, ['validateByBaseValidator', 'validateByOwnMethods'])) {
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

    /**
     * @param array             $input
     * @param array             $parsedRules
     * @param AppliesToResource $resource (optional)
     * @param string            $locale (optional)
     *
     * @return Validation
     **/
    protected function performValidation(array $input, array $parsedRules, AppliesToResource $resource=null, $locale=null)
    {

        // First split rules into base rules and the custom rules of this class
        list($baseRules, $ownRules) = $this->toBaseAndOwnRules($parsedRules);

        $validation = new ValidationException();
        $validation->setRules($parsedRules);

        if ($baseRules) {
            $this->validateByBaseValidator($validation, $input, $baseRules, $resource, $locale);
        }

        if ($ownRules) {
            $this->validateByOwnMethods($validation, $input, $ownRules, $resource, $locale);
        }
        return $validation;
    }

    /**
     * Perform all validation by the custom methods of this class
     *
     * @param Validation        $validation
     * @param array             $input
     * @param array             $ownRules
     * @param AppliesToResource $resource (optional)
     * @param string            $locale (optional)
     **/
    protected function validateByOwnMethods(Validation $validation, array $input, array $ownRules, AppliesToResource $resource=null, $locale=null)
    {
        foreach ($ownRules as $key=>$keyRules) {
            $vars = [
                'input'    => $input,
                'key'      => $key,
                'value'    => Helper::value($input, $key),
                'resource' => $resource,
                'locale'   => $locale
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
     * Perform the validation by an own validator method.
     *
     * @param string $ruleName
     * @param array  $vars       The validation variables (input, value, key, resource, locale)
     * @param array  $parameters The rule parameters (in rules array)
     *
     * @return bool
     **/
    protected function validateByOwnMethod($ruleName, array $vars, array $parameters)
    {
        $method = $this->getOwnValidationMethods()[$ruleName];

        $methodParams = Lambda::mergeArguments([$this, $method], $vars, $parameters);

        return call_user_func_array([$this, $method], $methodParams);
    }

    /**
     * Collects all relation keys
     *
     * @param array $rules
     *
     * @return array
     **/
    protected function collectRelations(array $flat)
    {

        $relations = [];

        foreach ($flat as $key=>$value) {

            if ($relation = $this->getRelationName($key)) {
                $relations[] = $relation;
            }

        }

        return array_values(array_unique($relations));

    }

    /** 
     * Return the relation name of a key. Just pop the last segment
     *
     * @param string $key
     *
     * @return string
     **/
    protected function getRelationName($key)
    {
        if (strpos($key, '.') === false) {
            return '';
        }

        $path = explode('.', $key);

        array_pop($path);

        return implode('.', $path);
    }

    /**
     * A little helper method if you work with xtype. Just quickly retrieve the
     * type of the resource (or a path on it)
     *
     * @param string $path (optional)
     *
     * @return \Emx\Contracts\XType\XType
     **/
    protected function xType($path=null)
    {
        if (!$this->typeProvider) {
            throw new UnConfiguredException("No typeprovider setted");
        }

        if (!$resource = $this->resource()) {
            throw new UnConfiguredException("Without returning something in resource() I cant find a type");
        }

        return $this->typeProvider->xType($resource, $path);
    }
}

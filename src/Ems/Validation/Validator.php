<?php


namespace Ems\Validation;

use Ems\Contracts\Core\ChangeTracking;
use Ems\Contracts\Core\Checker as CheckerContract;
use Ems\Contracts\Core\DataObject;
use Ems\Contracts\Core\Entity;
use Ems\Contracts\Core\HasInjectMethods;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Expression\Constraint;
use Ems\Contracts\Expression\ConstraintGroup;
use Ems\Contracts\Expression\ConstraintParsingMethods;
use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Validation\ValidationException;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Core\Checker;
use Ems\Core\Collections\NestedArray;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Helper;
use Ems\Core\Lambda;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Patterns\SnakeCaseCallableMethods;
use RuntimeException;
use stdClass;

use function array_key_exists;
use function array_merge;
use function is_object;
use function method_exists;


class Validator implements ValidatorContract, HasInjectMethods, HasMethodHooks
{
    use HookableTrait;
    use ConstraintParsingMethods;
    use SnakeCaseCallableMethods {
        SnakeCaseCallableMethods::isSnakeCaseCallableMethod as parentIsSnakeCaseCallableMethod;
    }

    /**
     * @var string
     */
    protected $ormClass = '';

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
    protected $extendedRules = [];

    /**
     * @var array
     **/
    protected $ownValidationMethods;

    /**
     * @var callable
     **/
    protected $ruleDetector;

    /**
     * This is the prefix for the "own validation methods" of this class.
     *
     * @var string
     */
    protected $snakeCasePrefix = 'validate';

    /**
     * @var CheckerContract
     */
    protected $checker;

    /**
     * @var string[]
     */
    protected $required_rules = ['required', 'required_if', 'required_unless'];

    public function __construct(array $rules=[], string $ormClass='')
    {
        if ($rules) {
            $this->rules = $rules;
        }
        $this->ormClass = $ormClass;
    }

    /**
     * @return stdClass
     */
    public function resource()
    {
        return new stdClass();
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function ormClass(): string
    {
        return $this->ormClass;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function rules() : array
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
     * @param array         $input      The input from a request or another input source like import files
     * @param object|null   $ormObject  An orm object that this data will belong to
     * @param array         $formats Pass information how to read the input see self::DATE_FORMAT etc.
     *
     * @return array Return a clean version of the input data that can be processed by a repository
     *
     * @throws ValidationException
     **/
    public function validate(array $input, $ormObject=null, array $formats=[]) : array
    {
        $rules = $this->prepareRulesForValidation($this->rules(), $input, $ormObject, $formats);

        $this->callBeforeListeners('validate', [$input, $rules, $ormObject, $formats]);

        $validation = new ValidationException();
        $validation->setRules($rules);

        $validated = $this->performValidation($input, $validation, $ormObject, $formats);

        $this->callAfterListeners('validate', [$input, $rules, $ormObject, $validation, $formats]);

        if (count($validation)) {
            throw $validation;
        }

        return $validated;
    }

    /**
     * @param array $rules
     *
     * @return self
     **/
    public function mergeRules(array $rules) : ValidatorContract
    {
        $this->extendedRules = array_merge($this->extendedRules, $rules);
        $this->parsedRules = null;
        return $this;
    }

    /**
     * @return bool
     */
    public function canMergeRules(): bool
    {
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
     * Set a rule detector to detect the rules for deferred loading of rules.
     * The detector will be called with the orm class and a depth or relation.
     *
     * $detector = function (string $ormClass, $relations=1) { return ['name' => 'required'] };
     *
     * @param callable $detector
     *
     * @return self
     **/
    public function detectRulesBy(callable $detector) : Validator
    {
        $this->ruleDetector = $detector;
        return $this;
    }

    /**
     * Perform validation by the base validator. Reimplement this method
     * to use it with your favourite base validator.
     *
     * @param Validation    $validation
     * @param array         $input
     * @param array         $baseRules
     * @param object|null   $ormObject (optional)
     * @param array         $formats (optional)
     *
     * @return array
     **/
    protected function validateByBaseValidator(Validation $validation, array $input, array $baseRules, $ormObject=null, array $formats=[]) : array
    {

        $validated = [];

        foreach ($baseRules as $key=>$rule) {

            if (!$this->checkRequiredRules($rule, $input, $key, $validation)) {
                continue;
            }

            $value = $input[$key] ?? null;

            foreach ($rule as $name=>$args) {
                if (in_array($name, $this->required_rules)) {
                    continue;
                }
                if (!$this->check($value, [$name=>$args], $ormObject, $formats)) {
                    $validation->addFailure($key, $name, $args);
                }
            }
            if (array_key_exists($key, $input)) {
                $validated[$key] = $this->cast($value, $rule, $ormObject, $formats);
            }
        }

        return $validated;
    }

    /**
     * A validation rule. It fails if the key was found in the input.
     *
     * @param array $input
     * @param string $key
     *
     * @return bool
     **/
    protected function validateForbidden($input, $key) : bool
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
     * @param string $ormClass
     *
     * @return array
     **/
    protected function detectRules(string $ormClass) : array
    {
        if (!$this->ruleDetector) {
            throw new UnConfiguredException("You have to assign a rule detector to detect rules");
        }
        return call_user_func($this->ruleDetector, $ormClass, $this->relations);
    }

    /**
     * This method is called just to have a hook to build initial rules.
     *
     * @return array
     **/
    protected function buildRules() : array
    {
        if ($this->rules) {
            return array_merge($this->rules, $this->extendedRules);
        }

        if ($ormClass = $this->ormClass()) {
            $this->rules = $this->detectRules($ormClass);
        }

        $this->rules = array_merge($this->rules, $this->extendedRules);

        return $this->rules;
    }

    /**
     * Here is the point where you should change validations caused by the
     * state of the resource (unique keys, exists,...) and depending on the
     * current locale.
     * It allows you to have simple rule expressions like date and then add
     * the localized date format later.
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

        $prepared = [];

        // Flatify to make nested checks more easy
        $flatInput = NestedArray::flat($input);

        $passedRelations = $this->collectRelations($flatInput);

        $entityExists = $this->isFromStorage($ormObject);

        foreach ($rules as $key=>$constraints) {

            $keyRules = [];

            foreach ($constraints as $constraint=>$parameters) {

                // The parent segment of the nested key
                $relation = $this->getRelationName($key);

                $relationNotInInput = $relation && !in_array($relation, $passedRelations);

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

        $own = $this->getSnakeCaseMethods();

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
     * Overwritten to skip the two validateBy... methods.
     *
     * @param string $method
     * @param string $prefix
     *
     * @return bool
     **/
    protected function isSnakeCaseCallableMethod($method, $prefix)
    {
        if (in_array($method, ['validateByBaseValidator', 'validateByOwnMethods'])) {
            return false;
        }
        return $this->parentIsSnakeCaseCallableMethod($method, $prefix);
    }

    /**
     * @param array         $input
     * @param Validation    $validation
     * @param object|null   $ormObject (optional)
     * @param array         $formats (optional)
     *
     * @return array
     **/
    protected function performValidation(array $input, Validation $validation, $ormObject=null, array $formats=[]) : array
    {

        // First split rules into base rules and the custom rules of this class
        list($baseRules, $ownRules) = $this->toBaseAndOwnRules($validation->rules());

        $validated = $input;

        if ($baseRules) {
            $validated = $this->validateByBaseValidator($validation, $input, $baseRules, $ormObject, $formats);
        }

        if (!$ownRules) {
            return $validated;
        }

        $this->validateByOwnMethods($validation, $input, $ownRules, $ormObject, $formats);

        return $validated;
    }

    /**
     * Perform all validation by the custom methods of this class
     *
     * @param Validation    $validation
     * @param array         $input
     * @param array         $ownRules
     * @param object|null   $ormObject (optional)
     * @param array         $formats (optional)
     **/
    protected function validateByOwnMethods(Validation $validation, array $input, array $ownRules, $ormObject=null, array $formats=[])
    {
        foreach ($ownRules as $key=>$keyRules) {
            $vars = [
                'input'     => $input,
                'key'       => $key,
                'value'     => Helper::value($input, $key),
                'ormObject' => $ormObject,
                'formats'   => $formats
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
     * Check if $value matches $rule.
     *
     * @param mixed                                   $value
     * @param ConstraintGroup|Constraint|array|string $rule
     * @param object|null                             $ormObject (optional)
     * @param array                                   $formats (optional)
     *
     * @return bool
     */
    protected function check($value, $rule, $ormObject = null, array $formats=[]) : bool
    {
        if (!$this->checker) {
            $this->checker = new Checker();
        }
        return $this->checker->check($value, $rule, $ormObject);
    }

    /**
     * Cast the value into something a repository can process.
     *
     * @param mixed                                   $value
     * @param ConstraintGroup|Constraint|array|string $rule
     * @param object|null                             $ormObject (optional)
     * @param array                                   $formats (optional)
     *
     * @return mixed
     */
    protected function cast($value, $rule, $ormObject=null, array $formats=[])
    {
        return $value;
    }

    /**
     * Check all required rules and return true if other rules should be checked
     * after.
     *
     * @param array $rule
     * @param array $input
     * @param string $key
     * @param Validation $validation
     * @return bool
     */
    protected function checkRequiredRules(array $rule, array $input, string $key, Validation $validation) : bool
    {
        $value = $input[$key] ?? null;
        // Check required before others to skip rest if missing
        if (isset($rule['required']) && !$this->checkRequired($input, $key)) {
            $validation->addFailure($key, 'required', []);
            return false;
        }

        if (isset($rule['required_if']) && !$this->checkRequiredIf($input, $key, $rule['required_if'])) {
            $validation->addFailure($key, 'required_if', $rule['required_if']);
            return false;
        }

        if (isset($rule['required_unless']) && !$this->checkRequiredUnless($input, $key, $rule['required_unless'])) {
            $validation->addFailure($key, 'required_unless', $rule['required_unless']);
            return false;
        }

        // Check if not required other rules are ignored
        if (!isset($rule['required']) && !$this->checkRequired($input, $key)) {
            return false;
        }



        return true;
    }

    /**
     * @param array $input
     * @param string $key
     * @return bool
     */
    protected function checkRequired(array $input, string $key) : bool
    {
        if (!isset($input[$key])) {
            return false;
        }
        return $this->check($input[$key], ['required' => []]);
    }

    /**
     * Check if value is present but only if another is also.
     *
     * @param array $input
     * @param string $key
     * @param array $args
     * @return bool
     */
    protected function checkRequiredIf(array $input, string $key, array $args=[]) : bool
    {
        if (!isset($args[0])) {
            throw new RuntimeException("Validation rules required_if and required_unless need another field name arg as minimum");
        }
        $other = $args[0];
        $otherValue = $input[$other] ?? null;

        $otherMatches = isset($args[1]) ? $args[1] == $otherValue : $this->checkRequired($input, $other);

        if (!$otherMatches) {
            return true;
        }
        return $this->checkRequired($input, $key);
    }

    /**
     * Check if value is present but only if other is not.
     *
     * @param array $input
     * @param string $key
     * @param array $args
     * @return bool
     */
    protected function checkRequiredUnless(array $input, string $key, array $args=[]) : bool
    {
        if (!isset($args[0])) {
            throw new RuntimeException("Validation rules required_if and required_unless need another field name arg as minimum");
        }
        $other = $args[0];
        $otherValue = $input[$other] ?? null;

        $otherMatches = isset($args[1]) ? $args[1] == $otherValue : $this->checkRequired($input, $other);

        if ($otherMatches) {
            return true;
        }
        return $this->checkRequired($input, $key);
    }

    /**
     * Perform the validation by an own validator method.
     *
     * @param string $ruleName
     * @param array $vars The validation variables (input, value, key, ormObject, locale)
     * @param array $parameters The rule parameters (in rules array)
     *
     * @return bool
     *
     * @throws \ReflectionException
     */
    protected function validateByOwnMethod(string $ruleName, array $vars, array $parameters) : bool
    {
        $method = $this->getMethodBySnakeCaseName($ruleName);

        $methodParams = Lambda::mergeArguments([$this, $method], $vars, $parameters);

        return call_user_func([$this, $method], ...$methodParams);
    }

    /**
     * Collects all relation keys
     *
     * @param array $flat
     *
     * @return array
     **/
    protected function collectRelations(array $flat) : array
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
    protected function getRelationName(string $key) : string
    {
        if (strpos($key, '.') === false) {
            return '';
        }

        $path = explode('.', $key);

        array_pop($path);

        return implode('.', $path);
    }

    /**
     * @param object|null $ormObject
     * @return bool
     */
    protected function isFromStorage($ormObject) : bool
    {
        // Everything that is not an object is new ;-)
        // This works here because we will not be able to determine previous
        // data to skip required values
        if (!is_object($ormObject)) {
            return false;
        }
        if ($ormObject instanceof Entity || $ormObject instanceof DataObject || $ormObject instanceof ChangeTracking) {
            return !$ormObject->isNew();
        }
        if (method_exists($ormObject, 'getId')) {
            return (bool)$ormObject->getId();
        }
        if (isset($ormObject->id)) {
            return (bool)$ormObject->id;
        }
        // Eloquent
        if (isset($ormObject->exists)) {
            return (bool)$ormObject->exists;
        }
        return false;
    }
}

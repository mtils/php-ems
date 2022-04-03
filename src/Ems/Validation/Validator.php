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
use Ems\Core\Helper;
use Ems\Core\Lambda;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Patterns\SnakeCaseCallableMethods;

use function array_key_exists;
use function array_merge;
use function call_user_func;
use function in_array;
use function is_object;
use function method_exists;


class Validator implements ValidatorContract, HasInjectMethods, HasMethodHooks
{
    use HookableTrait;
    use ConstraintParsingMethods;
    use SnakeCaseCallableMethods;

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
     * @var callable
     */
    protected $baseValidator;

    /**
     * @var string[]
     */
    protected $required_rules = ['required', 'required_if', 'required_unless'];

    public function __construct(array $rules=[], string $ormClass='', callable $baseValidator=null)
    {
        if ($rules) {
            $this->rules = $rules;
        }
        $this->ormClass = $ormClass;
        $this->baseValidator = $baseValidator;
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
        $this->rules = array_merge($this->rules, $rules);
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
        return call_user_func($this->getBaseValidator(), $validation, $input, $baseRules, $ormObject, $formats);
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
    protected function parseRules(array $rules) : array
    {
        return $this->parseConstraints($rules);
    }

    /**
     * This method is called just to have a hook to build initial rules.
     *
     * @return array
     **/
    protected function buildRules() : array
    {
        return $this->rules;
    }

    /**
     * Here is the point where you should change validations caused by the
     * state of the ormObject (unique keys, exists,...) and depending on the
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
     *
     * @return bool
     **/
    protected function isIgnoredSnakeCaseCallableMethod(string $method) : bool
    {
        return in_array($method, ['validateByBaseValidator', 'validateByOwnMethods']);
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
     * Get or create the base validator
     * @return callable
     */
    protected function getBaseValidator() : callable
    {
        if (!$this->baseValidator) {
            $this->baseValidator = new CheckerBaseValidator(new Checker());
        }

        return $this->baseValidator;
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

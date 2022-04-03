<?php
/**
 *  * Created by mtils on 03.04.2022 at 11:02.
 **/

namespace Ems\Validation;

use Ems\Contracts\Core\Checker;
use Ems\Contracts\Expression\Constraint;
use Ems\Contracts\Expression\ConstraintGroup;
use Ems\Contracts\Validation\Validation;

use RuntimeException;

use function array_key_exists;
use function in_array;

class CheckerBaseValidator
{
    /**
     * @var Checker
     */
    protected $checker;

    /**
     * @var string[]
     */
    protected $required_rules = ['required', 'required_if', 'required_unless'];

    public function __construct(Checker $checker)
    {
        $this->checker = $checker;
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
    public function validate(Validation $validation, array $input, array $baseRules, $ormObject=null, array $formats=[]) : array
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
     * Alias for self::validate(). For simple usage as base validator in Validator.
     *
     * @param Validation    $validation
     * @param array         $input
     * @param array         $baseRules
     * @param object|null   $ormObject (optional)
     * @param array         $formats (optional)
     *
     * @return array
     **/
    public function __invoke(Validation $validation, array $input, array $baseRules, $ormObject=null, array $formats=[]) : array
    {
        return $this->validate($validation, $input, $baseRules, $ormObject, $formats);
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
    public function cast($value, $rule, $ormObject=null, array $formats=[])
    {
        return $value;
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
            $this->checker = new \Ems\Core\Checker();
        }
        return $this->checker->check($value, $rule, $ormObject);
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
}
<?php


namespace Ems\Validation;

use Ems\Contracts\Validation\Validation as ValidationContract;
use Ems\Contracts\Validation\GenericValidator as GenericValidatorContract;
use Ems\Contracts\Validation\AlterableValidator as AlterableValidatorContract;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Patterns\HookableTrait;


/**
 * This is just a placeholder for tests or fast validator creation by a callable.
 * It is normally not allowed to have a generic and alterable validator at
 * once.
 **/
class GenericValidator extends Validator implements GenericValidatorContract, AlterableValidatorContract
{
    use RuleSettingSupport;
    use RuleMergingSupport;

    /**
     * @var callable
     **/
    protected $baseValidator;

    /**
     * @param array    $rules (optional)
     * @param callable $baseValidator (optional)
     **/
    public function __construct(array $rules=[], callable $baseValidator=null)
    {
        $this->setRules($rules);
        $this->baseValidator = $baseValidator;
    }

    /**
     * Perform validation by the the base validator. Reimplement this method
     * to use it with your favorite base validator.
     *
     * @param Validation        $validation
     * @param array             $input
     * @param array             $baseRules
     * @param AppliesToResource $resource (optional)
     * @param string            $locale (optional)
     **/
    protected function validateByBaseValidator(ValidationContract $validation, array $input, array $baseRules, AppliesToResource $resource=null, $locale=null)
    {
        if (!$this->baseValidator) {
            throw new UnConfiguredException("Assign a callable to do the validation");
        }
        call_user_func($this->baseValidator, $validation, $input, $baseRules, $resource, $locale);
    }

}

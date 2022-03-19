<?php


namespace Ems\Validation;

use Ems\Contracts\Core\HasMethodHooks;
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
class GenericValidator extends Validator implements GenericValidatorContract, AlterableValidatorContract, AppliesToResource, HasMethodHooks
{
    use RuleSettingSupport;
    use RuleMergingSupport;

    /**
     * @var callable
     **/
    protected $baseValidator;

    /**
     * @param array         $rules (optional)
     * @param callable|null $baseValidator (optional)
     **/
    public function __construct(array $rules=[], callable $baseValidator=null)
    {
        parent::__construct();
        $this->setRules($rules);
        $this->baseValidator = $baseValidator;
    }

    /**
     * Manually set the orm class.
     *
     * @param string $ormClass
     * @return self
     */
    public function setOrmClass(string $ormClass) : GenericValidator
    {
        $this->ormClass = $ormClass;
        return $this;
    }

    /**
     * Perform validation by the the base validator. Reimplement this method
     * to use it with your favorite base validator.
     *
     * @param ValidationContract    $validation
     * @param array                 $input
     * @param array                 $baseRules
     * @param object|null           $ormObject (optional)
     * @param array                 $formats (optional)
     *
     * @return array
     **/
    protected function validateByBaseValidator(ValidationContract $validation, array $input, array $baseRules, $ormObject=null, array $formats=[]) : array
    {
        if (!$this->baseValidator) {
            throw new UnConfiguredException("Assign a callable to do the validation");
        }
        return call_user_func($this->baseValidator, $validation, $input, $baseRules, $ormObject, $formats);
    }

}

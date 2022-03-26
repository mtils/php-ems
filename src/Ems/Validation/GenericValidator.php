<?php


namespace Ems\Validation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Validation\Validation as ValidationContract;
use Ems\Core\Exceptions\UnConfiguredException;


/**
 * This is just a placeholder for tests or fast validator creation by a callable.
 * It is normally not allowed to have a generic and alterable validator at
 * once.
 **/
class GenericValidator extends Validator implements AppliesToResource, HasMethodHooks
{
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
        $this->applyRules($rules);
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

    /**
     * Little method for less code duplication when extending GenericValidator
     * @param array $rules
     * @return void
     */
    protected function applyRules(array $rules)
    {
        $this->rules = $rules;
    }
}

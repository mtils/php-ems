<?php


namespace Ems\Validation;

use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Contracts\Core\AppliesToResource;

class GenericValidatorFactory implements ValidatorFactoryContract
{
    use ConfiguresValidator;

    /**
     * @var callable
     **/
    protected $factory;

    /**
     * Just assign a callable to do the validation.
     *
     * @param callable $factory (optional)
     **/
    public function __construct(callable $factory=null)
    {
        $this->makeBy($factory ?: function ($rules) {
            return new GenericValidator($rules);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @param array             $rules
     * @param AppliesToResource $resource (optional
     *
     * @return Validator
     **/
    public function make(array $rules, AppliesToResource $resource=null)
    {
        $validator = call_user_func($this->factory, $rules, $resource);
        return $this->configureAndReturn($validator, $rules, $resource);
    }

    /**
     * Just assign a callable to do the validation.
     *
     * @param callable $factory (optional)
     *
     * @return $this
     **/
    public function makeBy(callable $factory)
    {
        $this->factory = $factory;
        return $this;
    }

}

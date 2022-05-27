<?php

namespace Ems\Validation\Skeleton;


use Ems\Contracts\Validation\Validator;
use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Skeleton\Bootstrapper;
use Ems\Validation\Validator as GenericValidator;
use Ems\Validation\ValidatorFactory;

/**
 * @codeCoverageIgnore
 **/
class ValidationBootstrapper extends Bootstrapper
{
    protected $singletons = [
        ValidatorFactory::class  => ValidatorFactoryContract::class
    ];

    public function bind()
    {
        parent::bind();
        $this->container->bind(Validator::class, GenericValidator::class);
        $this->forwardDirectResolvingEventsToFactory();
    }

    /**
     * Forward resolving events of validators not created by the factory.
     *
     * @return void
     */
    protected function forwardDirectResolvingEventsToFactory()
    {
        $this->container->on(Validator::class, function (Validator $validator) {
            $factory = $this->container->get(ValidatorFactoryContract::class);
            if (!$factory instanceof ValidatorFactory) {
                return;
            }
            $factory->forwardValidatorEvent($validator);
        });
    }
}

<?php

namespace Ems\Validation\Skeleton;


use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Skeleton\Bootstrapper;
use Ems\Validation\ValidatorFactory;

/**
 * @codeCoverageIgnore
 **/
class ValidationBootstrapper extends Bootstrapper
{
    protected $singletons = [
        ValidatorFactory::class  => ValidatorFactoryContract::class
    ];
}

<?php

namespace Ems\Validation\Skeleton;


use Ems\Core\Skeleton\Bootstrapper;
use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Contracts\Validation\ResourceRuleDetector;
use Ems\Validation\Validator;
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

//         $this->app->resolving(Validator::class, function (Validator $validator) {
//             if (!$this->app->bound(ResourceRuleDetector::class)) {
//                 return;
//             }
//             $this->app->call([$validator, 'setRuleDetector']);
//         });

    }

}

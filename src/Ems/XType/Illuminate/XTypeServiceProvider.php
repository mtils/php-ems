<?php

namespace Ems\XType\Illuminate;

use Ems\Core\Laravel\BootstrapperAsServiceProvider;
use Ems\Validation\Illuminate\Validator;
use Ems\Validation\ValidatorFactory as ValidatorFactoryChain;
use Ems\XType\Skeleton\XTypeBootstrapper;

class XTypeServiceProvider extends BootstrapperAsServiceProvider
{
    /**
     * @return string
     **/
    protected function bootClass()
    {
        return XTypeBootstrapper::class;
    }

    public function register()
    {
        parent::register();
        $this->app->afterResolving(Validator::class, function (Validator $validator) {
            $validator->detectRulesBy(function ($ormClass, $relations=1) {
                /** @var XTypeProviderValidatorFactory $factory */
                $factory = $this->app->get(XTypeProviderValidatorFactory::class);
                return $factory->detectRules($ormClass, $relations);
            });
        });

        $this->app->singleton(XTypeProviderValidatorFactory::class);

        $this->app->afterResolving(ValidatorFactoryChain::class, function (ValidatorFactoryChain $factory) {
            $factory->addIfNoneOfClass($this->app->make(XTypeProviderValidatorFactory::class));
        });
    }

}

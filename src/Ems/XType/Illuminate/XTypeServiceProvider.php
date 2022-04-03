<?php

namespace Ems\XType\Illuminate;

use Ems\Contracts\XType\SelfExplanatory;
use Ems\Core\Laravel\BootstrapperAsServiceProvider;
use Ems\Validation\ValidatorFactory as ValidatorFactoryRegistry;
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

        $this->app->singleton(XTypeProviderValidatorFactory::class);

        $this->app->afterResolving(ValidatorFactoryRegistry::class, function (ValidatorFactoryRegistry $factory) {
            $factory->register(SelfExplanatory::class, function (string $ormClass) {
                /** @var XTypeProviderValidatorFactory $xtypeFactory */
                $xtypeFactory = $this->app->make(XTypeProviderValidatorFactory::class);
                return $xtypeFactory->validator($ormClass);
            });
        });
    }

}

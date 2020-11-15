<?php

namespace Ems\Validation\Illuminate;

use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Contracts\Core\TextProvider;
use Ems\Core\Laravel\BootstrapperAsServiceProvider;
use Ems\Validation\Skeleton\ValidationBootstrapper;
use Ems\Contracts\XType\TypeProvider;
use Ems\Contracts\Validation\ResourceRuleDetector;
use Ems\Contracts\Validation\ValidationConverter as ValidationConverterContract;
use Ems\Validation\ValidatorFactory as ValidatorFactoryChain;


class EmsValidationServiceProvider extends BootstrapperAsServiceProvider
{

    /**
     * @return string
     **/
    protected function bootClass()
    {
        return ValidationBootstrapper::class;
    }

    public function register()
    {
        parent::register();

        $this->app->afterResolving(ValidatorFactoryChain::class, function (ValidatorFactoryChain $factory) {
            $this->addFactories($factory);
        });

        $this->app->afterResolving(Validator::class, function (Validator $validator) {
//             $this->app->call([$validator, 'setIlluminateFactory']);
        });

        $this->app->singleton(ResourceRuleDetector::class, XTypeProviderValidatorFactory::class);

        $this->app->singleton(ValidationConverterContract::class, function ($app) {
            $textProvider = $app->make(TextProvider::class)->forDomain('validation');
            return $app->makeWith(ValidationConverter::class, ['textProvider' => $textProvider]);
        });
    }

    /**
     * @codeCoverageIgnore
     **/
    protected function addFactories(ValidatorFactoryChain $factoryChain)
    {
        // Add generic factory
        $factoryChain->addIfNoneOfClass($this->app->make(ValidatorFactory::class));

        // If no xtype is used do not add its factory
        if (!$this->app->bound(TypeProvider::class)) {
            return;
        }

        $factoryChain->addIfNoneOfClass($this->app->make(XTypeProviderValidatorFactory::class));
    }
}

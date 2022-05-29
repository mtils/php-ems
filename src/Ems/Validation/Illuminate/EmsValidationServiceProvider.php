<?php

namespace Ems\Validation\Illuminate;

use Ems\Contracts\Core\TextProvider;
use Ems\Contracts\Validation\ValidationConverter as ValidationConverterContract;
use Ems\Contracts\Validation\Validator;
use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Core\Laravel\BootstrapperAsServiceProvider;
use Ems\Validation\Skeleton\ValidationBootstrapper;
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

        $this->app->bind(Validator::class, function ($container, array $args) {
            $rules = $args['rules'] ?? [];
            $ormClass = $args['ormClass'] ?? '';
            /** @var ValidatorFactoryContract $factory */
            $factory = $this->app->make(ValidatorFactoryContract::class);
            return $factory->create($rules, $ormClass);
        });

        $this->app->afterResolving(ValidatorFactoryChain::class, function (ValidatorFactoryChain $factory) {
            $factory->setCreateFactory(function (array $rules, string $ormClass='') {
                /** @var ValidatorFactory $illuminateFactory */
                $illuminateFactory = $this->app->make(ValidatorFactory::class);
                return $illuminateFactory->validator($rules, $ormClass);
            });
        });

        $this->app->afterResolving(Validator::class, function (Validator $validator) {
            $factory = $this->app->make(ValidatorFactoryContract::class);
            if (!$factory instanceof ValidatorFactoryChain) {
                return;
            }
            $factory->forwardValidatorEvent($validator);
        });

        $this->app->singleton(ValidationConverterContract::class, function ($app) {
            $textProvider = $app->make(TextProvider::class)->forDomain('validation');
            return $app->makeWith(ValidationConverter::class, ['textProvider' => $textProvider]);
        });

    }

}

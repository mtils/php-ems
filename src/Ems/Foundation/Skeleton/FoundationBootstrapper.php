<?php

namespace Ems\Foundation\Skeleton;


use Ems\Core\Skeleton\Bootstrapper;
use Ems\Contracts\Foundation\InputNormalizer as InputNormalizerContract;
use Ems\Contracts\Foundation\InputNormalizerFactory as NormalizerFactoryContract;
use Ems\Foundation\InputNormalizer;
use Ems\Foundation\InputNormalizerFactory;


class FoundationBootstrapper extends Bootstrapper
{

    protected $singletons = [
        InputNormalizer::class  => InputNormalizerContract::class,
        InputNormalizerFactory::class => NormalizerFactoryContract::class
    ];

    public function bind()
    {

        parent::bind();


        $this->app->resolving(InputNormalizerFactory::class, function (InputNormalizerFactory $factory) {

            // Assign the container as an InputNormalizer creator
            $factory->createNormalizerBy(function () {
                return $this->app->make(InputNormalizerContract::class);
            });

            // Assign a default extension to omit the "not-found-errors"
            $factory->extend('*', function () {});
        });

    }

}

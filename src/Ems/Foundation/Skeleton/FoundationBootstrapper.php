<?php

namespace Ems\Foundation\Skeleton;


use Ems\Contracts\Core\Extractor;
use Ems\Skeleton\Bootstrapper;
use Ems\Contracts\Foundation\InputNormalizer as InputNormalizerContract;
use Ems\Contracts\Foundation\InputNormalizerFactory as NormalizerFactoryContract;
use Ems\Expression\Matcher;
use Ems\Foundation\InputNormalizer;
use Ems\Foundation\InputNormalizerFactory;
use Ems\Model\PhpSearchEngine;


class FoundationBootstrapper extends Bootstrapper
{

    protected $singletons = [
        InputNormalizer::class  => InputNormalizerContract::class,
        InputNormalizerFactory::class => NormalizerFactoryContract::class
    ];

    public function bind()
    {

        parent::bind();

        $this->container->bind(PhpSearchEngine::class, function ($ioc) {
            return new PhpSearchEngine($ioc(Matcher::class), $ioc(Extractor::class));
        });

        $this->container->on(InputNormalizerFactory::class, function (InputNormalizerFactory $factory) {
            // Assign a default extension to omit the "not-found-errors"
            $factory->extend('*', function () {});
        });

    }

}

<?php

namespace Ems\XType\Skeleton;


use Ems\Core\Skeleton\Bootstrapper;
use Ems\Contracts\XType\TypeFactory as TypeFactoryContract;
use Ems\Contracts\XType\TypeProvider as TypeProviderContract;
use Ems\XType\Eloquent\ModelTypeFactory;
use Ems\XType\Eloquent\ModelReflector;
use Ems\XType\Eloquent\RelationReflector;
use Ems\XType\TypeFactory;
use Ems\XType\TypeProvider;
use Illuminate\Database\Eloquent\Model;

class XTypeBootstrapper extends Bootstrapper
{
    /**
     * @var bool
     **/
    protected static $eloquentExists;

    protected $singletons = [
        TypeFactory::class  => TypeFactoryContract::class,
        TypeProvider::class => TypeProviderContract::class
    ];

    public function bind()
    {
        parent::bind();

        $this->app->resolving(TypeProvider::class, function ($provider) {
            $this->addEloquentExtensionsIfInstalled($provider);
        });
    }

    /**
     * This method is only for testing purposes
     *
     * @param bool $isInstalled
     **/
    public static function setEloquentInstalled($isInstalled)
    {
        static::$eloquentExists = $isInstalled;
    }

    /**
     * @param TypeProvider $provider
     **/
    protected function addEloquentExtensionsIfInstalled(TypeProvider $provider)
    {
        if (!$this->isEloquentInstalled()) {
            return;
        }

        // Make ModelTypeFactory a singleton
        $this->app->bind(ModelTypeFactory::class, function ($app) {
            return new ModelTypeFactory(
                $app(TypeFactoryContract::class),
                $app(ModelReflector::class),
                $app(RelationReflector::class)
            );
        });

        $provider->extend(Model::class, function ($model) {
            return $this->app->make(ModelTypeFactory::class)->toType($model);
        });
    }

    /**
     * @return bool
     **/
    protected function isEloquentInstalled()
    {
        if (static::$eloquentExists === null) {
            static::$eloquentExists = class_exists(Model::class);
        }
        return static::$eloquentExists;
    }
}

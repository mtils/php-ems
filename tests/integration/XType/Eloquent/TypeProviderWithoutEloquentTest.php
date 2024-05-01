<?php


namespace Ems\XType\Eloquent;


use Ems\Contracts\XType\TypeProvider as TypeProviderContract;
use Ems\XType\Eloquent\ModelTypeFactory;
use Ems\XType\NumberType;
use Ems\XType\Skeleton\XTypeBootstrapper;


class TypeProviderWithoutEloquentTest extends \Ems\IntegrationTest
{

    public function test_classes_are_not_loaded_if_eloquent_not_installed()
    {

        XTypeBootstrapper::setEloquentInstalled(false);
        $this->assertFalse($this->app()->bound(ModelTypeFactory::class));

        $type = $this->app(TypeProviderContract::class)->xType(15);
        $this->assertInstanceOf(NumberType::class, $type);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        XTypeBootstrapper::setEloquentInstalled(null);
    }
}

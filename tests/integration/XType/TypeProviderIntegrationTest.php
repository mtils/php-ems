<?php


namespace Ems\XType;

require_once(__DIR__.'/../../unit/XType/TypeProviderTest.php'); // Load fake models


use Ems\AppTrait;
use Ems\Contracts\Core\Extractor as ExtractorContract;
use Ems\Contracts\XType\TypeProvider as TypeProviderContract;
use Ems\Contracts\XType\TypeFactory as TypeFactoryContract;

class TypeProviderIntegrationTest extends TypeProviderTest
{
    use AppTrait;

    protected function xType($model)
    {
        return $this->provider()->toType($model);
    }

    protected function newProvider(ExtractorContract $extractor=null, TemplateTypeFactory $templateFactory=null, TypeFactory $typeFactory=null)
    {
        if (!$extractor && !$templateFactory) {
            return $this->app(TypeProviderContract::class);
        }
        return parent::newProvider($extractor, $templateFactory);
    }

    protected function newExtractor()
    {
        return $this->app(ExtractorContract::class);
    }

    protected function newTemplateFactory()
    {
        return $this->app(TemplateTypeFactory::class);
    }

    protected function newTypeFactory()
    {
        return $this->app(TypeFactoryContract::class);
    }

}

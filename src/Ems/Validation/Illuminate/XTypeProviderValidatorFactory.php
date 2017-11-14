<?php


namespace Ems\Validation\Illuminate;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Validation\ResourceRuleDetector;
use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Contracts\XType\TypeProvider;
use Ems\Core\Support\CustomFactorySupport;
use Ems\XType\Illuminate\XTypeToRuleConverter;

/**
 * This class retrievs the xtype of a parameter, converts
 * it into laravel validation rules and creates a
 * validator out of it.
 **/
class XTypeProviderValidatorFactory implements SupportsCustomFactory, ValidatorFactoryContract, ResourceRuleDetector
{
    use CustomFactorySupport;

    /**
     * @var TypeProvider
     **/
    protected $typeProvider;

    /**
     * @var XTypeToRuleConverter
     **/
    protected $ruleConverter;

    /**
     * @param TypeProvider          $typeProvider
     * @param XTypeToRuleConverter $ruleConverter
     **/
    public function __construct(TypeProvider $typeProvider, XTypeToRuleConverter $ruleConverter)
    {
        $this->typeProvider = $typeProvider;
        $this->ruleConverter = $ruleConverter;
    }

    /**
     * Create a validator for $rules and $resource
     *
     * @param array             $rules
     * @param AppliesToResource $resource (optional)
     *
     * @return \Ems\Contracts\Validation\Validator|null
     **/
    public function make(array $rules, AppliesToResource $resource=null)
    {

        if (!$resource) {
            return null;
        }

        $rules = array_merge($this->detectRules($resource), $rules);

        return $this->createObject(GenericValidator::class)->setRules($rules);

    }

    /**
     * Convert a resource into a laravel rule array.
     *
     * @param AppliesToResource $resource
     * @param int|array         $relationDepth (default:1)
     *
     * @return array
     **/
    public function detectRules(AppliesToResource $resource, $relationDepth=1)
    {
        $type = $this->typeProvider->xType($resource);
        return $this->ruleConverter->toRule($type, $relationDepth);
    }
}

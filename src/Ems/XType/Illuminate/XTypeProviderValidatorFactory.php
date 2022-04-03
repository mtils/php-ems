<?php


namespace Ems\XType\Illuminate;

use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Contracts\XType\TypeProvider;
use Ems\Contracts\XType\XType;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Validation\Illuminate\IlluminateBaseValidator;
use Ems\XType\SequenceType;
use Ems\Validation\Validator;

/**
 * This class retrieves the xtype of a parameter, converts
 * it into laravel validation rules and creates a
 * validator out of it.
 **/
class XTypeProviderValidatorFactory implements SupportsCustomFactory
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
     * @param string $ormClass
     *
     * @return ValidatorContract|null
     *
     * @throws \ReflectionException
     */
    public function validator(string $ormClass) : ValidatorContract
    {

        $rules = $this->detectRules($ormClass);
        return $this->createObject(Validator::class, [
            'rules'         => $rules,
            'ormClass'      => $ormClass,
            'baseValidator' => $this->createObject(IlluminateBaseValidator::class)
        ]);

    }

    /**
     * Convert a xtype into a laravel rule array.
     *
     * @param string    $ormClass
     * @param int|array $relationDepth (default:1)
     *
     * @return array
     **/
    public function detectRules(string $ormClass, $relationDepth=1)
    {
        $type = $this->typeProvider->xType($ormClass);
        return $this->filterDetectedRules($this->ruleConverter->toRule($type, $relationDepth), $ormClass);
    }

    /**
     * Apply some filtering on the detected rules. If they are provided by
     * a TypeProvider they contain really all keys, also readonly
     *
     * @param array       $rules
     * @param string|null $ormClass
     *
     * @return array
     **/
    protected function filterDetectedRules(array $rules, string $ormClass) : array
    {

        $filteredRules = [];

        foreach ($rules as $key=>$rule) {

            if (!$type = $this->typeProvider->xType($ormClass, $key)) {
                continue;
            }

            if ($this->shouldRemoveRules($key, $type)) {
                continue;
            }

            if ($type->readonly) {
                $filteredRules[$key] = 'forbidden';
                continue;
            }

            $filteredRules[$key] = $rule;
        }

        return $filteredRules;
    }

    /**
     * Return true if rules for a key should be removed from the detected rules
     *
     * @param string $key
     * @param XType  $type
     *
     * @return bool
     **/
    protected function shouldRemoveRules($key, XType $type)
    {

        if ($type instanceof SequenceType) {
            return true;
        }

        if ($type->isComplex()) {
            return true;
        }

        return false;
    }

}

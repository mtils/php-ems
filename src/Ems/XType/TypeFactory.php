<?php


namespace Ems\XType;


use Ems\Contracts\XType\TypeFactory as TypeFactoryContract;
use Ems\Contracts\Core\Extendable;
use Ems\Core\Helper;
use Ems\Core\Patterns\ExtendableTrait;
use InvalidArgumentException;
use Ems\Core\Exceptions\ResourceNotFoundException;


/**
 * This is the default xtype factory. Create types by a string or an
 * assoziative array containing the shortcut keys of an xtype.
 **/
class TypeFactory implements TypeFactoryContract, Extendable
{

    use ExtendableTrait;

    /**
     * @var array
     **/
    protected $typeCache = [];

    /**
     * {@inheritdoc}
     *
     * @param mixed $config
     *
     * @return bool
     **/
    public function canCreate($config)
    {
        return is_string($config) || $this->isArrayWithNonNumericKeys($config);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $config
     *
     * @return \Ems\Contracts\XType\XType
     **/
    public function toType($config)
    {
        if (!$this->canCreate($config)) {
            throw new InvalidArgumentException('Cannot create an xtype out of parameter. Please check with canCreate first.');
        }

        if (is_string($config)) {
            return $this->stringToType($config);
        }

        $type = new ArrayAccessType;

        foreach ($config as $key=>$value) {
            $type[$key] = $this->stringToType($value);
        }

        return $type;

    }

    /**
     * Creates a type by a string and fills it
     *
     * @param string $config
     *
     * @return AbstractType
     **/
    protected function stringToType($config)
    {

        list($typeName, $properties) = $this->splitTypeAndProperties($config);
        $type = $this->createType($typeName);

        if ($properties) {
            $type->fill($this->parseProperties($properties));
        }

        return $type;

    }

    /**
     * Creates a type by a name
     *
     * @param string $typeName
     *
     * @return AbstractType
     **/
    protected function createType($typeName)
    {

        if (isset($this->typeCache[$typeName])) {
            return clone $this->typeCache[$typeName];
        }


        if (!$this->hasExtension($typeName)) {
            $class = $this->typeToClassName($typeName);
            $this->typeCache[$typeName] = new $class;
            return $this->typeCache[$typeName];
        }

        $this->typeCache[$typeName] = $this->callExtension($typeName, [$typeName, $this]);

        return $this->typeCache[$typeName]->setName($typeName);
    }

    /**
     * Parses all property string of an array into single arrays
     *
     * @param array $properties
     *
     * @return array
     **/
    protected function parseProperties(array $properties)
    {

        $parsed = [];

        foreach ($properties as $propertyString) {

            if (mb_strpos($propertyString, ':')) {
                list($key, $value) = explode(':', $propertyString, 2);
                $parsed[$key] = $value;
                continue;
            }

            $value = true;
            $key = $propertyString;

            if ($propertyString[0] == '!') {
                $value = false;
                $key = mb_substr($propertyString, 1);
            }

            $parsed[$key] = $value;

        }

        return $parsed;
    }

    /**
     * Splits the type name from the properties
     *
     * @param string $rule
     *
     * @return array
     **/
    protected function splitTypeAndProperties($rule)
    {
        $parts = explode('|', $rule);
        $typeName = array_shift($parts);
        return [$typeName, $parts];
    }

    /**
     * Translate the type name to a class name
     *
     * @param string $typeName
     *
     * @return string
     **/
    protected function typeToClassName($typeName)
    {

        $classBase = Helper::studlyCaps($typeName) . 'Type';

        $class = __NAMESPACE__ . "\\$classBase";

        if (class_exists($class)) {
            return $class;
        }

        $class = __NAMESPACE__ . "\UnitTypes\\$classBase";

        if (class_exists($class)) {
            return $class;
        }

        throw new ResourceNotFoundException("XType class for $typeName not found");

    }

    /**
     * @param mixed $value
     *
     * @return bool
     **/
    protected function isArrayWithNonNumericKeys($value)
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $key=>$value) {
            if (is_numeric($key)) {
                return false;
            }
        }

        return true;
    }

}

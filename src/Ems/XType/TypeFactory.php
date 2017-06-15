<?php

namespace Ems\XType;


use Ems\Contracts\XType\TypeFactory as TypeFactoryContract;
use Ems\Contracts\XType\SelfExplanatory;
use Ems\Contracts\XType\HasTypedItems;
use Ems\Contracts\XType\XType;
use Ems\Contracts\Core\Extendable;
use Ems\Core\Helper;
use Ems\Core\Patterns\ExtendableTrait;
use InvalidArgumentException;
use Ems\Core\Exceptions\ResourceNotFoundException;
use UnexpectedValueException;

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
        return is_string($config) || $this->isArrayWithNonNumericKeys($config) || $config instanceof SelfExplanatory;
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
            throw new InvalidArgumentException('Cannot create an xtype out of parameter. Please check with canCreate first. Received '.Helper::typeName($config));
        }

        if (is_string($config)) {
            return $this->stringToType($config);
        }

        if (!$config instanceof SelfExplanatory) {
            return $this->fillType(new ArrayAccessType(), $config);
        }

        $typeInfo = $config->xTypeConfig();

        if ($typeInfo instanceof ObjectType) {
            return $typeInfo;
        }

        $type = new ObjectType();
        $type->class = get_class($config);

        return $this->fillType($type, $typeInfo);
    }

    /**
     * Fill the passed type with the types inside $config
     *
     * @param KeyValueType $type
     * @param array        $config
     *
     * @return KeyValueType
     **/
    protected function fillType(KeyValueType $type, $config)
    {
        $parsed = $this->parseConfig($config);
        foreach ($parsed as $key=>$value) {
            $type[$key] = $parsed[$key];
        }

        return $type;
    }

    /**
     * Parses each array value to a type
     *
     * @param array|\Traversable $config
     *
     * @return array
     **/
    protected function parseConfig($config)
    {
        $parsed = [];

        foreach ($config as $key=>$value) {
            if ($value instanceof XType) {
                $parsed[$key] = $value;
                continue;
            }

            $parsed[$key] = $this->stringToType($value);
        }

        return $parsed;
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

        if (!$type instanceof ObjectType) {
            return $type;
        }

        if (!$type->hasKeyProvider()) {
            $type->provideKeysBy($this->buildKeyProvider($type));
        }

        return $type;
    }

    /**
     * Creates a deferred key provider to avoid loading the complete object
     * on first access
     *
     * @param ObjectType $type
     *
     * @return \Closure
     **/
    protected function buildKeyProvider(ObjectType $type)
    {
        $keyClass = $type->class;

        return function () use ($keyClass, &$type) {
            $root = new $keyClass();
            $config = $root->xTypeConfig();
            return $this->parseConfig($config);

        };
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
            $this->typeCache[$typeName] = new $class();
            return clone $this->typeCache[$typeName];
        }

        $this->typeCache[$typeName] = $this->callExtension($typeName, [$typeName, $this]);

        return clone $this->typeCache[$typeName]->setName($typeName);
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
            if (!mb_strpos($propertyString, ':')) {
                list($key, $value) = $this->parseBooleanShortcut($propertyString);
                $parsed[Helper::camelCase($key)] = $value;
                continue;
            }


            list($key, $value) = explode(':', $propertyString, 2);


            if (!$this->isNestedShortCut($value)) {
                $parsed[Helper::camelCase($key)] = $value;
                continue;
            }

            $type = $this->stringToType(trim($value, '[]'));

            $parsed[Helper::camelCase($key)] = $type;
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
        // A nested rule
        if (!mb_strpos($rule, ':[')) {
            $parts = explode('|', $rule);
            $typeName = array_shift($parts);
            return [$typeName, $parts];
        }

        $levels = [];

        $chars = Helper::stringSplit($rule);

        $level = 0;
        $isFirst = true;
        $typeName = '';
        $parts = [];
        $currentPart = -1;

        foreach ($chars as $char) {
            if ($char == '[') {
                $level++;
            }

            if ($char == ']') {
                $level--;
            }

            if ($char == '|' && $level == 0) {
                $isFirst = false;
                $currentPart++;
                continue;
            }

            if ($isFirst) {
                $typeName .= $char;
                continue;
            }


            if (!isset($parts[$currentPart])) {
                $parts[$currentPart] = '';
            }

            $parts[$currentPart] .= $char;
        }

        return [$typeName, $parts];
    }

    /**
     * Determine if the passed rule is a nested one
     *
     * @param string $propertyString
     *
     * @return bool
     **/
    protected function isNestedShortCut($propertyString)
    {
        return strpos($propertyString, '[') === 0;
    }

    /**
     * Parse a boolean shortcut (property without :)
     *
     * @param string $propertyString
     *
     * @return array
     **/
    protected function parseBooleanShortcut($propertyString)
    {
        $value = true;
        $key = $propertyString;

        if ($propertyString[0] == '!') {
            $value = false;
            $key = mb_substr($propertyString, 1);
        }

        return [$key, $value];
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
        $classBase = Helper::studlyCaps($typeName).'Type';

        $class = __NAMESPACE__."\\$classBase";

        if (class_exists($class)) {
            return $class;
        }

        $class = __NAMESPACE__."\UnitTypes\\$classBase";

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

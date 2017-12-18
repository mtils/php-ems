<?php

namespace Ems\XType\Eloquent;

use Ems\Contracts\XType\TypeFactory;
use Ems\Contracts\Core\Type;
use Ems\XType\ObjectType;
use Ems\XType\SequenceType;
use Ems\Core\Exceptions\MisConfiguredException;
use Illuminate\Database\Eloquent\Model;
use Ems\Core\Exceptions\UnsupportedParameterException;

/**
 * The ModelTypeFactory returns types for eloquent models. You should implement
 * SelfExplanatory and return detailled type information to enhance its results.
 **/
class ModelTypeFactory
{
    /**
     * @var TypeFactory
     **/
    protected $typeFactory;

    /**
     * @var ModelReflector
     **/
    protected $reflector;

    /**
     * @var RelationReflector
     **/
    protected $relationReflector;

    /**
     * @var array
     **/
    protected $typeCache = [];

    /**
     * @param TypeFactory       $typeFactory
     * @param ModelReflector    $reflector
     * @param RelationReflector $relationReflector
     **/
    public function __construct(TypeFactory $typeFactory, ModelReflector $reflector,
                                RelationReflector $relationReflector)
    {
        $this->typeFactory = $typeFactory;
        $this->reflector = $reflector;
        $this->relationReflector = $relationReflector;
    }

    /**
     * @param Model|string $model
     *
     * @return ObjectType
     */
    public function toType($model)
    {
        list($instance, $class) = $this->instanceAndClass($model);

        if ($type = $this->getFromCache($class)) {
            return $type;
        }

        $config = $this->buildConfig($instance, $class);

        $objectType = $this->typeFactory->toType("object|class:$class");

        $objectType->provideKeysBy(function () use (&$config) {
            return $this->typeFactory->toType($config);
        });

        return $this->putIntoCacheAndReturn($objectType);
    }

    /**
     * Merge the autodetected rules with (optional) manually setted, translate
     * the relations and return the complete result.
     *
     * @param Model  $instance
     * @param string $class
     *
     * @return array
     **/
    protected function buildConfig(Model $instance, $class)
    {
        $autoRules = $this->getConfigFromReflector($instance);
        $manualRules = $this->getManualConfig($instance);

        $keys = $manualRules ? array_keys($manualRules) : array_keys($autoRules);

        $config = [];

        foreach ($keys as $key) {

            // Give manually setted rules priority
            if (!isset($manualRules[$key]) && isset($autoRules[$key])) {
                $config[$key] = $autoRules[$key];
                continue;
            }

            $config[$key] = $manualRules[$key];

            if ($this->isSequenceTypeRule($config[$key])) {
                $keyType = $this->createSequenceType($config[$key]);
                $config[$key] = $keyType;
                continue;
            }

            if ($this->isObjectTypeRule($config[$key])) {
                $keyType = $this->createObjectType($config[$key]);
                $config[$key] = $this->putIntoCacheAndReturn($keyType);
                continue;
            }
        }

        // Assign not manually setted rules if they dont exist
        foreach ($autoRules as $key=>$rule) {

            // Give manually setted rules priority
            if (!isset($config[$key])) {
                $config[$key] = $autoRules[$key];
            }
        }

        return $config;
    }

    /**
     * (Bogus) determine if the rule is a rule for an object type
     *
     * @param string $rule
     *
     * @return bool
     **/
    protected function isObjectTypeRule($rule)
    {
        return strpos($rule, 'object|') === 0;
    }

    /**
     * (Bogus) determine if the rule is a rule for an sequence type
     *
     * @param string $rule
     *
     * @return bool
     **/
    protected function isSequenceTypeRule($rule)
    {
        return strpos($rule, 'sequence|') === 0;
    }

    /**
     * Create a deferred object type for $config. Object types need to be
     * created to provide the keys later by this object.
     *
     * @param string $config
     *
     * @return ObjectType
     **/
    protected function createObjectType($config)
    {
        $keyType = $this->typeFactory->toType($config);

        $keyType->provideKeysBy(function () use ($keyType) {

            $cls = $keyType->class;
            $instance = new $cls();
            $config = $this->buildConfig($instance, $cls);

            return $this->typeFactory->toType($config);
        });

        return $keyType;
    }

    /**
     * Create sequence type with a deffered itemType for $config.
     *
     * @param string $config
     *
     * @return SequenceType
     **/
    protected function createSequenceType($config)
    {
        $keyType = $this->typeFactory->toType($config);

        if (!$keyType->itemType) {
            throw new MisConfiguredException('ModelFactory can only care about SequenceTypes with an itemType');
        }

        $itemType = $keyType->itemType;

        if (!$itemType instanceof ObjectType) {
            return $keyType;
        }

        $itemType->provideKeysBy(function () use ($itemType) {

            $cls = $itemType->class;
            $instance = new $cls();
            $config = $this->buildConfig($instance, $cls);

            return $this->typeFactory->toType($config);
        });

        return $keyType;
    }

    /**
     * Return the manually setted config of Model or an empty array
     *
     * @param Model $model
     *
     * @return array
     **/
    protected function getManualConfig(Model $model)
    {
        if (!method_exists($model, 'xTypeConfig')) {
            return [];
        }

        $config = $model->xTypeConfig();
        $parsed = [];
        $foreignKeys = [];

        foreach ($config as $key=>$rule) {
            if ($rule != 'relation') {
                $parsed[$key] = $rule;
                continue;
            }

            $relation = $this->relationReflector->buildRelationXTypeInfo($model, $key);

            $parsed[$key] = $relation['type'];

            if ($relation['foreign_keys']) {
                $foreignKeys += $relation['foreign_keys'];
            }
        }

        return $parsed;
    }

    /**
     * Load the automatically detected rules
     *
     * @param Model $model
     *
     * @return array
     **/
    protected function getConfigFromReflector(Model $model)
    {
        $config = [];
        foreach ($this->reflector->keys($model) as $key) {
            $config[$key] = $this->reflector->typeString($model, $key);
        }
        return $config;
    }

    /**
     * Return an instance of a model and a classname
     *
     * @param mixed $model
     *
     * @return array
     **/
    protected function instanceAndClass($model)
    {

        // Checks without instantiating first
        if (!is_subclass_of($model, Model::class)) {
            throw new UnsupportedParameterException('ModelTypeFactory only supports Eloquent models not '.Type::of($model) );
        }

        return is_object($model) ? [$model, get_class($model)] : [new $model(), $model];
    }

    /**
     * @param string $class
     *
     * @return ObjectType|null
     **/
    protected function getFromCache($class)
    {
        return isset($this->typeCache[$class]) ? $this->typeCache[$class] : null;
    }

    /**
     * @param string     $class
     * @param ObjectType $type
     *
     * @return ObjectType
     **/
    protected function putIntoCacheAndReturn(ObjectType $type)
    {
        $this->typeCache[$type->class] = $type;
        return $type;
    }
}

<?php

namespace Ems\XType\Illuminate;

use Ems\Contracts\XType\XType;
use Ems\Contracts\Core\Extendable;
use Ems\XType\ArrayAccessType;
use Ems\XType\KeyValueType;
use Ems\XType\SequenceType;
use Ems\Core\Collections\StringList;
use Ems\Core\Collections\NestedArray;
use Ems\Core\Patterns\ExtendableTrait;

/**
 * This class convertes xtype instances to laravel validation
 * rules.
 **/
class XTypeToRuleConverter implements Extendable
{

    use ExtendableTrait;

    /**
     * A map from xtype to validation rule
     *
     * @var array
     **/
    protected $typeToRule = [
        'array-access' => 'array',
        'bool'         => 'boolean',
        'boolean'      => 'boolean',
        'number'       => 'numeric',
        'sequence'     => 'array',
        'string'       => 'string',
        'temporal'     => 'date',
        'unit'         => 'numeric',
        'array'        => 'array'
    ];

    /**
     * A map from constraint name to rule
     *
     * @var array
     **/
    protected $constraintToRule = [
        'min'          => 'min',
        'max'          => 'max'
    ];


    public function __construct()
    {
        $this->addBaseExtensions();
    }

    /**
     * Convert a xtype object to validation rule(s). Restrict the depth for
     * traversing the relations by an int or by a passed array of relation
     * names.
     *
     * @example $converter->toRule($userType, 2); // Convertes root and all direct relations
     * @example $converter->toRule($userType, ['address', 'tags'] // Convertes root and relations address and tags
     *
     * @param XType     $type
     * @param int|array $depthOrRelations (default:1) search relations by depth or filter.
     *
     * @return string|array
     **/
    public function toRule(XType $type, $depthOrRelations=1)
    {

        $path = [];

        $relations = is_array($depthOrRelations) ? $this->addMissingParentRelations($depthOrRelations) : [];
        $maxDepth = is_array($depthOrRelations) ? $this->maxDepthFromRelations($relations) : $depthOrRelations;

        $rule = $this->toRuleRecursive($type, $maxDepth, $relations, $path);

        return is_string($rule) ? $rule : NestedArray::flat($rule);


    }

    /**
     * @param XType $type
     * @param int   $maxDepth
     * @param array $relations
     * @param array $path
     *
     * @return string|array
     **/
    protected function toRuleRecursive(XType $type, $maxDepth, array $relations, array &$path)
    {

        if (!$type instanceof KeyValueType) {

            $rule = [];

            $this->addRequiredIfNotNull($type, $rule);

            $this->addGroupName($type, $rule);

            $this->addConstraints($type, $rule);

            return implode('|', $rule);
        }

        // Check if the depth would be higher then max
        if ((count($path)+1) > $maxDepth) {
            return $this->groupName($type);
        }

        return $this->keyValueTypeToRule($type, $maxDepth, $relations, $path);


    }

    /**
     * Convert a KeyValueType to a rule (array)
     *
     * @param KeyValueType $type
     * @param int          $maxDepth
     * @param array        $relations
     * @param array        $path
     *
     * @return array|string
     **/
    protected function keyValueTypeToRule(KeyValueType $type, $maxDepth, array $relations, array &$path)
    {

        $rules = [];

        foreach ($type as $key=>$keyType) {

            $path[] = $key;

            if ($relations && $keyType instanceof KeyValueType && !in_array(implode('.', $path), $relations)) {
                array_pop($path);
                continue;
            }

            $rules[$key] = $this->toRuleRecursive($type[$key], $maxDepth, $relations, $path);

            array_pop($path);

        }

        return $rules;

    }

    /**
     * Find a good type group name as validation rule.
     *
     * @param XType $type
     *
     * @return string
     **/
    protected function groupName(XType $type)
    {

        if ($type instanceof ArrayAccessType) {
            return 'array';
        }

        if ($type instanceof SequenceType) {
            return 'array';
        }

        return $type->group();

    }

    /**
     * Add the required rule.
     *
     * @param XType $type
     * @param array $rule
     **/
    protected function addRequiredIfNotNull(XType $type, &$rule)
    {
        if (isset($type->not_null) && $type->not_null) {
            $rule[] = $this->callExtension('not_null', [true]);
        }
    }

    /**
     * Add the group name to the rule
     *
     * @param XType $type
     * @param array $rule
     **/
    protected function addGroupName(XType $type, &$rule)
    {
        $groupName = $this->groupName($type);

        if (isset($this->typeToRule[$groupName])) {
            $rule[] = $this->typeToRule[$groupName];
        }
    }

    /**
     * Add the constraints as rules
     *
     * @param XType $type
     * @param array $rule
     **/
    protected function addConstraints(XType $type, &$rule)
    {

        foreach ($type->constraints as $name=>$parameters) {

            if ($name == 'not_null') {
                continue;
            }

            if (isset($this->constraintToRule[$name])) {
                $constraintName = $this->constraintToRule[$name];
                $rule[] = $constraintName . ':' . implode($parameters);
            }

        }
    }

    /**
     * Some basic extensions
     **/
    protected function addBaseExtensions()
    {
        $this->extend('not_null', function ($notNull) {
            if ($notNull) {
                return 'required';
            }
        });
    }

    /**
     * Calculate the max depth from the passed relations
     *
     * @param array $relations
     *
     * @return int
     **/
    protected function maxDepthFromRelations(array $relations)
    {

        if (!$relations) {
            return 1;
        }

        $max = 1;

        foreach ($relations as $path) {
            $max = max($max, count(explode('.', $path)));
        }

        // max has to be relation depth +1 because we want to have the
        // keys of that relation too
        return (int)$max + 1;

    }

    /**
     * If you pass a relation filter of only address.country.territory
     * this method adds the not passed address and address.country relations.
     *
     * @param array $relations
     *
     * @return array
     **/
    protected function addMissingParentRelations(array $relations)
    {

        $filled = $relations;

        foreach ($relations as $relation) {

            $segments = explode('.', $relation);
            $path = [];
            foreach ($segments as $segment) {
                $path[] = $segment;
                $pathString = implode('.', $path);
                if (!in_array($pathString, $relations)) {
                    $filled[] = $pathString;
                }
            }
        }

        return $filled;

    }
}
